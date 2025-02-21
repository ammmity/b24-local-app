<?php

namespace App\Controllers;

use App\Services\CRestService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UsersController {
    public function __construct(
        protected CRestService $CRestService
    )
    {}

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $filter = [];
        if (!empty($args['find'])) {
            $filter['FIND'] = "%{$args['find']}%";
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
}
