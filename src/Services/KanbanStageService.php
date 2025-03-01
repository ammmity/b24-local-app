<?php

namespace App\Services;

use App\Services\CRestService;

class KanbanStageService
{

    public function __construct(
        protected CRestService $CRestService,
    )
    {}

    /**
     * Получает или создает стадии канбана для группы
     *
     * @param string $groupId ID группы
     * @return array Массив стадий
     */
    public function getOrCreateStages($groupId): array
    {
        $stages = [];
        $kanbanStages = $this->CRestService->kanbanStages($groupId);

        if (empty($kanbanStages) || !is_array($kanbanStages)) {
            return $stages;
        }

        $stageTemplates = $this->getStageTemplates($groupId);

        foreach ($stageTemplates as $template) {
            $stageId = $this->findOrCreateStage($template, $kanbanStages);
            if ($stageId) {
                $stages[] = [
                    'id' => $stageId,
                    'title' => $template['TITLE'],
                ];
            }
        }

        return $stages;
    }

    /**
     * Находит или создает стадию канбана
     *
     * @param array $template Шаблон стадии
     * @param array $existingStages Существующие стадии
     * @return string|null ID стадии или null в случае ошибки
     */
    private function findOrCreateStage(array $template, array $existingStages): ?string
    {
        if (!isset($template['TITLE'])) {
            return null;
        }

        // Ищем существующую стадию
        $existingStage = $this->findStageByTitle($template['TITLE'], $existingStages);

        if ($existingStage && isset($existingStage['ID'])) {
            return $existingStage['ID'];
        }

        // Создаем новую стадию
        if ($this->validateStageTemplate($template)) {
            return $this->CRestService->addGroupStage([
                'fields' => $template,
                'isAdmin' => true
            ]);
        }

        return null;
    }

    /**
     * Ищет стадию по названию
     *
     * @param string $title Название стадии
     * @param array $stages Массив стадий
     * @return array|null Найденная стадия или null
     */
    private function findStageByTitle(string $title, array $stages): ?array
    {
        foreach ($stages as $stage) {
            if (isset($stage['TITLE']) && $stage['TITLE'] === $title) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Проверяет валидность шаблона стадии
     *
     * @param array $template Шаблон стадии
     * @return bool Результат проверки
     */
    private function validateStageTemplate(array $template): bool
    {
        $requiredFields = ['TITLE', 'COLOR', 'AFTER_ID', 'ENTITY_ID'];

        foreach ($requiredFields as $field) {
            if (!isset($template[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает шаблоны стадий для производства
     *
     * @param string $entityId ID сущности
     * @return array Массив шаблонов стадий
     */
    private function getStageTemplates($entityId): array
    {
        return [
            [
                "TITLE" => "Завершены",
                "COLOR" => "00a64c",
                "AFTER_ID" => 0,
                "ENTITY_ID" => $entityId
            ],
            [
                "TITLE" => "В работе",
                "COLOR" => "75d900",
                "AFTER_ID" => 0,
                "ENTITY_ID" => $entityId
            ],
            [
                "TITLE" => "Нет сырья",
                "COLOR" => "ef3000",
                "AFTER_ID" => 0,
                "ENTITY_ID" => $entityId
            ],
            [
                "TITLE" => "В ожидании",
                "COLOR" => "00c4fb",
                "AFTER_ID" => 0,
                "ENTITY_ID" => $entityId
            ],
        ];
    }
}
