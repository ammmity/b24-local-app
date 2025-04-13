<?php

namespace App\Middlewares;

use App\CRest\CRestCurrentUser;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CRestService;
use App\Settings\SettingsInterface;

class AuthMiddleware implements MiddlewareInterface
{
    use UnauthorizedResponseTrait;

    public const ATTR_USER = 'user';
    public const ATTR_IS_TEHNOLOG = 'isTehnolog';

    public function __construct(
        private CRestService $CRestService,
        private SettingsInterface $settings
    ) {}

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Проверяем действительно ли юзер пришел из интерфейса Битрикс24
        $queryParams = $request->getQueryParams();
        $postData = $request->getParsedBody();

        list($appToken, $domain) = [$queryParams['APP_SID'] ?? false, $queryParams['DOMAIN'] ?? false];
        list($refreshId, $authId) = [$postData['REFRESH_ID'] ?? false, $postData['AUTH_ID'] ?? false];

        $isUserFromb24 = !empty($authId)
            || !empty($domain)
            || !empty($refreshId)
            || !empty($appToken);

        if (!$isUserFromb24) {
            return $this->unauthorizedResponse();
        }

        // Получаем текущего пользователя из б24
        CRestCurrentUser::setDataExt([
            'APP_SID' => $queryParams['APP_SID'],
            'DOMAIN' => $queryParams['DOMAIN'],
            'REFRESH_ID' => $postData['REFRESH_ID'],
            'AUTH_ID' => $postData['AUTH_ID']
        ]); // для работы с API в контексте текущего пользователя
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
}
