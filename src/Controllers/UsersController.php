<?php

namespace App\Controllers;

use App\Settings\SettingsInterface;
use App\Services\CRestService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UsersController {
    public function __construct(
        protected CRestService $CRestService,
        protected SettingsInterface $settings
    )
    {}

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $filter = [];
        if (!empty($queryParams['find'])) {
            $filter['FIND'] = "%{$queryParams['find']}%";
        }

//        if (empty($filter)) {} // TODO: add paging


        $users = $this->CRestService->callMethod('user.search',
            [
                'filter' => $filter
            ]
        )['result'];

        if (!empty($users)) {
            $users = array_map(fn($user) => [
                'id' => $user['ID'],
                'name' => $user['NAME'],
                'last_name' => $user['LAST_NAME']
            ], $users);
        }

        $response->getBody()->write(json_encode($users));
        return $response;
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = [];
        $id = $args['id'];
        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Parameter id is required']));
            return $response;
        }

        $user = $this->CRestService->callMethod('user.get', [
            'filter' => ['ID' => $id]
        ])['result'];

        if (empty($user)) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404);
        } else {
            $user = array_map(fn($user) => (object)[
                'id' => $user['ID'],
                'name' => $user['NAME'],
                'last_name' => $user['LAST_NAME']
            ], $user);
            $userResource = (object) $user[0];
        }

        $response->getBody()->write(json_encode($userResource));
        return $response;
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $currentUser = $this->CRestService->currentUser();


        if ($this->settings->isProduction()) {
            $areUserTehnolog = in_array($this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'], $currentUser['UF_DEPARTMENT']);
        } else {
            $areUserTehnolog = true;
        }

        $currentUser['IS_TEHNOLOG'] = $areUserTehnolog;

        $response->getBody()->write(json_encode($currentUser));
        return $response;
    }

    public function getSystemUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $systemUserId = $this->settings->get('b24')['SYSTEM_USER_ID'];

        $response->getBody()->write(json_encode($systemUserId));
        return $response;
    }
}
