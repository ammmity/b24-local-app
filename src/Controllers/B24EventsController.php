<?php

namespace App\Controllers;

use App\Services\CRestService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class B24EventsController
{
    public function __construct(
        private CRestService $CRestService
    ) {}

    public function bindEventHandlers(Request $request, Response $response): Response
    {
        $result = $this->CRestService->eventBind(
            'onTaskUpdate',
            'http://furama.crm-kmz.ru/production-app/api/b24-events/task-updated'
        );

        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function taskUpdated(Request $request, Response $response): Response
    {
        $postData = $request->getParsedBody();
        $logData = date('Y-m-d H:i:s') . ' - ' . json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logDir . '/taskUpdatedHandler.log', $logData, FILE_APPEND);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response;
    }
}
