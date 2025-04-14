<?php

namespace App\Services;

use App\Entities\BitrixGroupKanbanStage;
use App\Entities\Good;
use App\Entities\ProductionScheme;
use App\Entities\ProductionSchemeStage;
use App\Entities\ProductProductionStage;
use App\Services\CRestService;
use App\Services\ProductStoresAndDocumentsService;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entities\OperationLog;
use App\Entities\OperationPrice;


class ProductionSchemeService
{

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected SettingsInterface $settings,
        protected ProductStoresAndDocumentsService $productStoresAndDocumentsService
    )
    {}

    protected function createCompletionLog(ProductionScheme $scheme): void
    {
        // Создаем запись в журнале для каждой стадии
        foreach ($scheme->getStages() as $completedStage) {
            $stageData = $completedStage->toArray();

            $user = $this->CRestService->callMethod('user.get', [
                'filter' => ['ID' => $stageData['executor_id']]
            ])['result'];

            $operationPrice = $this->entityManager
                ->getRepository(OperationPrice::class)
                ->findOneBy([
                    'operationType' => $stageData['operation_type']['id'],
                    'productPart' => $stageData['product_part']['id']
                ]);

            // Если не нашли специфичную цену для детали, ищем общую цену для операции
            if (!$operationPrice) {
                $operationPrice = $this->entityManager
                    ->getRepository(OperationPrice::class)
                    ->findOneBy([
                        'operationType' => $stageData['operation_type']['id'],
                        'productPart' => null
                    ]);
            }

            $operationLog = new OperationLog(
                taskLink: $this->settings->get('appUrl') . "company/personal/user/{$completedStage->getExecutorId()}/tasks/task/view/{$completedStage->getBitrixTaskId()}/",
                bitrixTaskId: (int)$completedStage->getBitrixTaskId(),
                dealId: (int)$scheme->getDealId(),
                detailId: (int)$stageData['product_part']['id'],
                detailName: $stageData['product_part']['name'],
                quantity: (int)$stageData['quantity'],
                username: $user[0]['NAME'] . ' ' . $user[0]['LAST_NAME'],
                userId: (int)$user[0]['ID'],
                price: $operationPrice ? $operationPrice->getPrice() : 0,
                operation: $stageData['operation_type']['name']
            );

            $this->entityManager->persist($operationLog);
        }

        $this->entityManager->flush();
    }

    public function getCurrentVirtualParts(ProductionScheme $scheme): array
    {
        $virtualParts = [];

        $productionSchemeStages = $scheme->getStages();
        /* @var ProductionSchemeStage $productionSchemeStage  */
        foreach ($productionSchemeStages as $productionSchemeStage) {
            $isCompletedOrFirstStage =
                $productionSchemeStage->getStatus() === ProductionSchemeStage::STATUS_COMPLETED
                || $productionSchemeStage->getStageNumber() === 1;

            if ($isCompletedOrFirstStage) {
                continue;
            }

            $productPart = $productionSchemeStage->toArray()['product_part']['id'];

            $prevProductionSchemeStage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                ->findOneBy([
                    'scheme' => $productionSchemeStage->getScheme(),
                    'stageNumber' => $productionSchemeStage->getStageNumber() - 1,
                    'productPart' => $productPart
                ]);

            if (!$prevProductionSchemeStage) {
                // Обработка ситуации, когда предыдущая стадия не найдена
                continue;
            }

            // Предыдущая стадия завершена, значит на виртуальном складе есть виртуальная деталь
            if ($prevProductionSchemeStage->getStatus() === ProductionSchemeStage::STATUS_COMPLETED) {
                $prevVirtualPart = $this->entityManager->getRepository(ProductProductionStage::class)
                ->findOneBy(['productPart' => $prevProductionSchemeStage->toArray()['product_part']['id'], 'operationType' => $prevProductionSchemeStage->toArray()['operation_type']['id']])->getResult();
                if (!empty($prevVirtualPart)) {
                    $virtualParts[] = array_merge($prevVirtualPart->toArray(), ['quantity' => $prevProductionSchemeStage->getQuantity()]);
                }
            }
        }

        return $virtualParts;
    }

    // TODO: Декомпозировать метод
    public function updateSchemeStages(string|int $taskId)
    {
        try {
            $currentUser = $this->CRestService->currentUser();

            $stage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                ->findOneBy(['bitrixTaskId' => $taskId]);
            $groupId = $stage->toArray()['operation_type']['bitrix_group_id'];
            $bitrix24Task = $this->CRestService->getTask($stage->getBitrixTaskId(), ['ID', 'STAGE_ID', 'STATUS', 'RESPONSIBLE_ID']);

            if ($stage->getExecutorId() != $bitrix24Task['responsibleId']) {
                $stage->setExecutorId($bitrix24Task['responsibleId']);
            }

            $kanbanStage = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findOneBy([
                'bitrix_group_id' => $groupId,
                'stage_id' => $bitrix24Task['stageId']
            ]);

            $stage->setStatus($kanbanStage->getStageName());

            if ($stage->getStatus() === 'Завершены') {


                $productPart = $stage->toArray()['product_part']['id'];

                // При создании детали ложим результат работы на виртуальный склад
                $baseProductProductionStage = $this->entityManager->getRepository(ProductProductionStage::class)
                    ->findOneBy(['productPart' => $stage->toArray()['product_part']['id'], 'operationType' => $stage->toArray()['operation_type']['id']]);

                if ($this->productStoresAndDocumentsService->isDocumentModeEnabled()) {
                    $virtualPart = $baseProductProductionStage->getResult();
                    if (!empty($virtualPart)) {
                        try {
                            $rs = $this->productStoresAndDocumentsService->addProductRemains(
                                (int) $virtualPart->getBitrixId(),
                                (int) $this->settings->get('b24')['VIRTUAL_STORE_ID'],
                                (int) $stage->getQuantity()
                            );

                        } catch (\Exception $e) {
                            $logDir = dirname(__DIR__, 2) . '/logs';
                            if (!is_dir($logDir)) {
                                mkdir($logDir, 0755, true);
                            }
                            file_put_contents($logDir . '/taskUpdatedHandlerERRORVirtualPart.log', print_r($e->getMessage(), 1), FILE_APPEND);
                        }
                    }
                    // При создании детали удаляем предыдущий виртуальный товар с вирт. склада
                    if ($stage->getStageNumber() > 1) {
                        $prevStage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                            ->findOneBy([
                                'productPart' => $productPart,
                                'stageNumber' => $stage->getStageNumber() - 1,
                                'scheme' => $stage->getScheme()->getId()
                            ]);

                        $prevBaseProductProductionStage = $this->entityManager->getRepository(ProductProductionStage::class)
                            ->findOneBy(['productPart' => $prevStage->toArray()['product_part']['id'], 'operationType' => $prevStage->toArray()['operation_type']['id']]);
                        $prevVirtualPart = $prevBaseProductProductionStage->getResult();

                        if (!empty($prevVirtualPart)) {
                            $this->productStoresAndDocumentsService->removeProductFromStore(
                                (int) $prevVirtualPart->getBitrixId(),
                                (int) $this->settings->get('b24')['VIRTUAL_STORE_ID'],
                                (int) $prevStage->getQuantity()
                            );
                        }
                    }
                }

                $nextStage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                    ->findOneBy([
                        'productPart' => $productPart,
                        'stageNumber' => $stage->getStageNumber() + 1,
                        'scheme' => $stage->getScheme()->getId()
                    ]);

                if (!empty($nextStage) && !$nextStage->getBitrixTaskId()) {
                    $title = $nextStage->toArray()['operation_type']['name']
                        . ' / ' . $nextStage->toArray()['product_part']['name']
                        . ' / ' . $nextStage->toArray()['quantity'] . 'шт';
                    $nextStageGroupId = $nextStage->toArray()['operation_type']['bitrix_group_id'];
                    $nextStageWaitingKanbanStage = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findOneBy([
                        'bitrix_group_id' => $nextStage->toArray()['operation_type']['bitrix_group_id'],
                        'stage_name' => 'В ожидании'
                    ]);

                    $b24TaskFields = [
                        'TITLE' => $title, // Название задачи
                        'CREATED_BY' => $currentUser['ID'], // Идентификатор постановщика
                        'RESPONSIBLE_ID' => $nextStage->getExecutorId(), // Идентификатор исполнителя
                        'STAGE_ID' => (int) $nextStageWaitingKanbanStage->getStageId(), // Стадия
                        'GROUP_ID' => $nextStageGroupId, // Группа
                        'UF_CRM_TASK' => [
                            'D_'.$nextStage->getScheme()->getDealId() // Привязка к сделке
                        ],
                    ];

//                    if ((int) $nextStage->getExecutorId() === (int) $this->settings->get('b24')['SYSTEM_USER_ID']) {
//                        $b24TaskFields['ACCOMPLICES'] = array_map(fn($user) => $user['USER_ID'], $this->CRestService->getGroupUsers($nextStageGroupId));
//                    }

                    if ((int) $nextStage->getExecutorId() === (int) $this->settings->get('b24')['SYSTEM_USER_ID']) {
                        $groupUsers = $this->CRestService->getGroupUsers($nextStageGroupId);
                        $groupUsersExceptCreator = array_filter($groupUsers, fn($user) => $user['ROLE'] !== 'A');
                        $b24TaskFields['ACCOMPLICES'] = array_map(fn($user) => $user['USER_ID'], $groupUsersExceptCreator);
                    }


                    $b24Task = $this->CRestService->addTask([
                        'fields' => $b24TaskFields
                    ]);
                    $nextStage->setBitrixTaskId($b24Task['id']);
                    $nextStage->setStatus('В ожидании');
                }
            }


            // Если все стадии завершены - переводим сделку в ProductionScheme::done
            $areAllStagesDone = true;
            foreach ($stage->getScheme()->getStages() as $stage) {
                if ($stage->getStatus() !== 'Завершены') {
                    $areAllStagesDone = false;
                }
            }

            $scheme = $stage->getScheme();
            if ($areAllStagesDone) {
                $scheme->setStatus(ProductionScheme::STATUS_DONE);
                $this->entityManager->persist($scheme);
                $this->log('Статус схемы изменен на DONE');
                $this->addFinishedDealProductsToStore($stage->getScheme());
                $this->createCompletionLog($scheme);
                $this->log('createCompletionLog Лог завершения создан');
            }


            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }

    }

    /**
     * Актуализирует cхему: статус сделки ( сделка в производстве!) и статусы задач.
     *
     * @param string $dealId ID сделки
     */
    public function sync(string|int $dealId)
    {
        $scheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $dealId]);

        if (!$scheme || $scheme->getStatus() !== ProductionScheme::STATUS_PROGRESS) {
            return false;
        }

        /** @var ProductionSchemeStage $stage */
        foreach ($scheme->getStages() as $stage) {
            if (!$stage->getBitrixTaskId()) {
                continue;
            }

            $groupId = $stage->toArray()['operation_type']['bitrix_group_id'];
            $bitrix24Task = $this->CRestService->getTask($stage->getBitrixTaskId(), ['ID', 'STAGE_ID', 'RESPONSIBLE_ID']);
            $kanbanStage = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findOneBy([
                'bitrix_group_id' => $groupId,
                'stage_id' => $bitrix24Task['stageId']
            ]);

            if ($stage->getExecutorId() != $bitrix24Task['responsibleId']) {
                $stage->setExecutorId($bitrix24Task['responsibleId']);
            }

            $stage->setStatus($kanbanStage->getStageName());
        }

        $this->entityManager->flush();

        // Получаем свежие данные после обновления
        $this->entityManager->refresh($scheme);

        $scheme->getStages()->toArray();

        return $scheme;
    }

    /**
     * Добавляет готовые продукты сделки на склад после завершения производства
     */
    private function addFinishedDealProductsToStore(ProductionScheme $scheme): bool
    {
        if ($scheme->getStatus() !== ProductionScheme::STATUS_DONE) {
            return false;
        }

        $productionStoreId = $this->settings->get('b24')['PRODUCTION_STORE_ID'];
        $goodsRepository = $this->entityManager->getRepository(Good::class);
        $title = 'Модуль производства: Завершение производства товаров сделки ';
        $comment = $title .= '('.$this->productStoresAndDocumentsService->getStoreName($productionStoreId).')';
        $documentId = $this->CRestService->addCatalogDocument($title, $comment)['id'];

        $dealId = $scheme->getDealId();
        $dealProducts = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $dealId])['result'] ?? null;
        foreach ($dealProducts as $dealProduct) {
            $this->CRestService->addElementToCatalogDocument(
                $documentId,
                0,
                $productionStoreId,
                $dealProduct['PRODUCT_ID'],
                isset($dealProduct['QUANTITY']) ? (int)$dealProduct['QUANTITY'] : 1
            );
        }

        $this->CRestService->conductDocument((int) $documentId);

        return true;
    }

    public function log($data, $fileName = '4test.log')
    {
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/'.$fileName, print_r([$data], 1), FILE_APPEND);
    }
}
