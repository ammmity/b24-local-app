<?php

namespace App\Services;

use App\Entities\BitrixGroupKanbanStage;
use App\Entities\ProductionScheme;
use App\Entities\ProductionSchemeStage;
use App\Services\CRestService;
use Doctrine\ORM\EntityManagerInterface;

class ProductionSchemeService
{

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
    )
    {}

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

//        $kanbanStages = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findAll();


        /** @var ProductionSchemeStage $stage */
        foreach ($scheme->getStages() as $stage) {
            if (!$stage->getBitrixTaskId()) {
                continue;
            }

            $groupId = $stage->toArray()['operation_type']['bitrix_group_id'];
            $bitrix24Task = $this->CRestService->getTask($stage->getBitrixTaskId(), ['ID', 'STAGE_ID']);
//            return $bitrix24Task;
            $kanbanStage = $this->entityManager->getRepository(BitrixGroupKanbanStage::class)->findOneBy([
                'bitrix_group_id' => $groupId,
                'stage_id' => $bitrix24Task['stageId']
            ]);

            $stage->setStatus($kanbanStage->getStageName());
        }

        $this->entityManager->flush();

        // Получаем свежие данные после обновления
        $this->entityManager->refresh($scheme);

        $scheme->getStages()->toArray();

        return $scheme;
    }
}
