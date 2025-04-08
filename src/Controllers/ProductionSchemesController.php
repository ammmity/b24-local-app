<?php

namespace App\Controllers;

use App\Settings\SettingsInterface;
use App\Entities\BitrixGroupKanbanStage;
use App\Entities\ProductionScheme;
use App\Entities\ProductionSchemeStage;
use App\Entities\ProductPart;
use App\Entities\OperationType;
use App\Services\CRestService;
use App\Services\ProductionSchemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductionSchemesController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected CRestService $CRestService,
        protected ProductionSchemeService $productionSchemeService,
        protected SettingsInterface $settings
    ) {}

    public function get(Request $request, Response $response, array $args): Response
    {
        $dealId = $args['id'] ?? null;

        if (!$dealId) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        $scheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $dealId]);

        if (!$scheme) {
            $response->getBody()->write(json_encode(null));
            return $response;
        }

        // Принудительно инициализируем коллекцию этапов
        // $scheme->getStages()->toArray();

        // Получаем свежие данные
        // $this->entityManager->refresh($scheme);

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['deal_id'])) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        // Проверяем, существует ли уже схема для данной сделки
        $existingScheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $data['deal_id']]);

        if ($existingScheme) {
            $response->getBody()->write(json_encode(
                ['error' => 'Схема производства уже существует для данной сделки']
            ));
            return $response;
        }

        $scheme = new ProductionScheme($data['deal_id']);

        if (isset($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $stageData) {
                if (!$this->validateStageData($stageData)) {
                    $response->getBody()->write(json_encode(['error' => 'Неизвестные данные этапа']));
                    return $response;
                }

                $productPart = $this->entityManager->getRepository(ProductPart::class)
                    ->find($stageData['product_part_id']);
                $operationType = $this->entityManager->getRepository(OperationType::class)
                    ->find($stageData['operation_type_id']);

                if (!$productPart || !$operationType) {
                    $response->getBody()->write(json_encode(
                        ['error' => 'Не найдены данные детали или операции']
                    ));
                    return $response;
                }

                $stage = new ProductionSchemeStage(
                    $scheme,
                    $productPart,
                    $operationType,
                    $stageData['stage_number'],
                    $stageData['quantity']
                );

                if (isset($stageData['executor_id'])) {
                    $stage->setExecutorId($stageData['executor_id']);
                }
                if (isset($stageData['bitrix_task_id'])) {
                    $stage->setBitrixTaskId($stageData['bitrix_task_id']);
                }

                $scheme->addStage($stage);
            }
        }

        $this->entityManager->persist($scheme);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $dealId = $args['id'] ?? null;

        if (!$dealId) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        $currentUser = $this->CRestService->currentUser();

        $scheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $dealId]);

        if (!$scheme) {
            $response->getBody()->write(json_encode(['error' => 'Схема производства не найдена']));
            return $response;
        }

        $data = json_decode($request->getBody()->getContents(), true);


        // Запустить производство
        if (
            isset($data['status'])
            && $data['status'] === 'progress'
            && !isset($data['stages'])
        ) {
            /** @var ProductionSchemeStage $stage */
            foreach ($scheme->getStages() as $stage) {
                if (!$stage->getExecutorId()) {
                    $stage->setExecutorId($this->settings->get('b24')['SYSTEM_USER_ID']);
                }

                // При создании производства, к первым стадиям создаются задачи
                if ($stage->getStageNumber() == 1) {
                    $title = $stage->toArray()['operation_type']['name']
                        . ' / ' . $stage->toArray()['product_part']['name']
                        . ' / ' . $stage->toArray()['quantity'] . 'шт';
                    $groupId = $stage->toArray()['operation_type']['bitrix_group_id'];
                    $waitingStage = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findOneBy([
                        'bitrix_group_id' => $groupId,
                        'stage_name' => 'В ожидании'
                    ]);

                    $b24TaskFields = [
                        'TITLE' => $title,
                        'CREATED_BY' => $currentUser['ID'],
                        'RESPONSIBLE_ID' => $stage->getExecutorId(),
                        'STAGE_ID' => (int) $waitingStage->getStageId(),
                        'GROUP_ID' => $groupId,
                        'UF_CRM_TASK' => [
                            'D_'.$dealId // Привязка к сделке
                        ],
                    ];

                    if ((int) $stage->getExecutorId() === (int) $this->settings->get('b24')['SYSTEM_USER_ID']) {
                        $b24TaskFields['ACCOMPLICES'] = array_map(fn($user) => $user['USER_ID'], $this->CRestService->getGroupUsers($groupId));
                    }

                    $b24Task = $this->CRestService->addTask([
                        'fields' => $b24TaskFields
                    ]);
                    $stage->setBitrixTaskId($b24Task['id']);
                    $stage->setStatus('В ожидании');
                }
            }
        }

        if (isset($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $stageData) {
                $stage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                    ->findOneBy([
                        'scheme' => $scheme,
                        'productPart' => $stageData['product_part_id'],
                        'operationType' => $stageData['operation_type_id']
                    ]);

                if ($stage) {
                    // Обновляем существующий этап
                    if (isset($stageData['executor_id'])) {
                        if (
                            $stageData['executor_id'] !== $stage->getExecutorId()
                            && !empty($stage->getBitrixTaskId())
                        ) {
                            $this->CRestService->updateTask([
                                'taskId' => $stage->getBitrixTaskId(),
                                'fields' => [
                                    'RESPONSIBLE_ID' => $stageData['executor_id']
                                ]
                            ]);

                        }
                        $stage->setExecutorId($stageData['executor_id']);
                    }
                    if (isset($stageData['bitrix_task_id'])) {
                        $stage->setBitrixTaskId($stageData['bitrix_task_id']);
                    }
                    if (isset($stageData['status'])) {
                        $stage->setStatus($stageData['status']);
                    }
                    if (isset($stageData['quantity'])) {
                        $stage->setQuantity($stageData['quantity']);
                    }
//                    if (isset($stageData['bitrix_task_id'])) {
//                        $stage->setBitrixTaskId($stageData['bitrix_task_id']);
//                    }
                }
            }
        }

        if (isset($data['status'])) {
            $scheme->setStatus($data['status']);
        }

        $this->entityManager->flush();

        // Получаем свежие данные после обновления
        $this->entityManager->refresh($scheme);

        // Принудительно инициализируем коллекцию этапов
        $scheme->getStages()->toArray();

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    public function sync(Request $request, Response $response, array $args): Response
    {
        $dealId = $args['id'] ?? null;

        if (!$dealId) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        try {
            $scheme = $this->productionSchemeService->sync($dealId);

            if (empty($scheme)) {
                $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
                return $response;
            }

            $response->getBody()->write(json_encode($scheme->toArray()));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response;
        }


        $response->getBody()->write(json_encode($scheme));
        return $response;
    }

    public function updateSchemeStagesManually(Request $request, Response $response, array $args): Response
    {
        $taskId = $args['taskId'] ?? null;
        if (empty($taskId)) {
            $response->getBody()->write(json_encode(['error' => 'Не указан taskId']));
            return $response;
        }

        $this->productionSchemeService->updateSchemeStages($taskId);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response;
    }

    private function validateStageData(array $data): bool
    {
        $requiredFields = ['product_part_id', 'operation_type_id', 'stage_number', 'quantity'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }
}
