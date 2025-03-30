<?php

namespace App\Controllers;

use App\Services\CRestService;
use App\Services\ProductionSchemeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class B24EventsController
{
    public function __construct(
        private CRestService $CRestService,
        private ProductionSchemeService $productionSchemeService
    ) {}

    public function bindEventHandlers(Request $request, Response $response): Response
    {
        $result = $this->CRestService->eventBind(
            'onTaskUpdate',
            'https://furama.crm-kmz.ru/production-app/public/api/b24-events/task-updated'
        );

        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function taskUpdated(Request $request, Response $response): Response
    {
        $postData = $request->getParsedBody();
        $taskId = $postData['data']['FIELDS_AFTER']['ID'];
        // Пытаемся получить блокировку
        $lockFile = $this->acquireLock($taskId);
        if (!$lockFile) {
            $response->getBody()->write(json_encode(['status' => 'locked']));
            return $response;
        }

        if (!$taskId) {
            $response->getBody()->write(json_encode(['status' => 'taskId required']));
            return $response;
        }

        try {
            if ($postData['event'] === 'ONTASKUPDATE') {
//                $logData = print_r($postData, 1);
//                $logDir = dirname(__DIR__, 2) . '/logs';
//                if (!is_dir($logDir)) {
//                    mkdir($logDir, 0755, true);
//                }
//                file_put_contents($logDir . '/taskUpdatedHandler.log', $logData, FILE_APPEND);

                $this->productionSchemeService->updateSchemeStages($taskId);
            }

            // Освобождаем блокировку
            $this->releaseLock($lockFile);

            $response->getBody()->write(json_encode(['success' => true]));
            return $response;

        } catch (\Exception $e) {
            // В случае ошибки тоже освобождаем блокировку
            $this->releaseLock($lockFile);
            throw $e;
        }

        $response->getBody()->write(json_encode(['success' => true]));
        return $response;
    }

    private function acquireLock(string $taskId): ?string
    {
        $lockFile = sys_get_temp_dir() . "/task_lock_{$taskId}.lock";

        if (file_exists($lockFile)) {
            $lockTime = filectime($lockFile);
            // Если блокировка старше 30 секунд, удаляем её
            if (time() - $lockTime > 2) {
                unlink($lockFile);
            } else {
                return null;
            }
        }

        file_put_contents($lockFile, time());
        return $lockFile;
    }

    private function releaseLock(?string $lockFile): void
    {
        if ($lockFile && file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}
