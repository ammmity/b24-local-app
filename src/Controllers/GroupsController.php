<?php

namespace App\Controllers;

use App\Services\CRestService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupsController
{
    public function __construct(
        private CRestService $CRestService
    ) {}

    public function list(Request $request, Response $response): Response
    {
        try {
            $groups = $this->CRestService->groups([
                'filter' => [],
                'select' => ['ID', 'NAME']
            ]);

            // Преобразуем данные в нужный формат
            $formattedGroups = array_map(function($group) {
                return [
                    'id' => $group['id'],
                    'name' => $group['name']
                ];
            }, $groups);

            $response->getBody()->write(json_encode($formattedGroups));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Ошибка при получении списка групп: ' . $e->getMessage()
            ]));
            return $response;
        }
    }
}
