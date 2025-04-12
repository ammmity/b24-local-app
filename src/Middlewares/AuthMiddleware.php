<?php

namespace App\Middlewares;

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CRestService;
use App\Settings\SettingsInterface;

class AuthMiddleware
{
    public const ATTR_USER = 'user';
    public const ATTR_IS_TEHNOLOG = 'isTehnolog';

    public function __construct(
        private CRestService $CRestService,
        private SettingsInterface $settings
    ) {}

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Получаем текущего пользователя из б24
        $currentUser = $this->CRestService->currentUser();
        
        // Проверяем, авторизован ли пользователь
        if (!$currentUser) {
            return $this->unauthorizedResponse();
        }

        // Проверяем, является ли пользователь технологом
        $isTehnolog = in_array(
            $this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'],
            $currentUser['UF_DEPARTMENT']
        );

        // Передаем текущего пользователя и признак технолога в запрос
        $request = $request
            ->withAttribute(self::ATTR_USER, $currentUser)
            ->withAttribute(self::ATTR_IS_TEHNOLOG, $isTehnolog);
        
        return $handler->handle($request);
    }

    private function unauthorizedResponse(): Response
    {
        $response = new \Slim\Psr7\Response(401);
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => 'Пользователь не авторизован'
        ]));
        return $response;
    }
} 