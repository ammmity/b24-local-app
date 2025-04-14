<?php
namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use App\CRest\CRestCurrentUser;
use App\Settings\Settings;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AppController {

    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected SettingsInterface $settings
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $requestParams = $request->getParsedBody();
        $userAuth = [
            'DOMAIN' => $queryParams['DOMAIN'] ?? false,
            'APP_SID' => $queryParams['APP_SID'] ?? false,
            'AUTH_ID' => $queryParams['AUTH_ID'] ?? $requestParams['AUTH_ID'] ?? false,
            'REFRESH_ID' => $queryParams['REFRESH_ID'] ?? $requestParams['REFRESH_ID'] ?? false,
        ];
        CRestCurrentUser::setDataExt($userAuth); // для работы с API в контексте текущего пользователя
        $currentUser = $this->CRestService->currentUser();

        if ($this->settings->isProduction()) {
            $isUserTehnolog = in_array($this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'], $currentUser['UF_DEPARTMENT']);
        } else {
            $isUserTehnolog = true;
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'app.html.twig', [
            'isUserTehnolog' => $isUserTehnolog,
            'isProduction' => $this->settings->isProduction(),
            'userAuth' => $userAuth,
        ]);
    }

    public function dealProductionScheme(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $requestParams = $request->getParsedBody();
        $dealId = null;

        if (!empty($requestParams['PLACEMENT_OPTIONS'])) {
            $dealData = json_decode($requestParams['PLACEMENT_OPTIONS'], true)['ID'];
            if (!empty($dealData)) {
                $dealId = $dealData;
            }
        }

        if (!empty($queryParams['id'])) {
            $dealId = $queryParams['id'];
        }

        $userAuth = [
            'DOMAIN' => $queryParams['DOMAIN'] ?? false,
            'APP_SID' => $queryParams['APP_SID'] ?? false,
            'AUTH_ID' => $requestParams['AUTH_ID'] ?? false,
            'REFRESH_ID' => $requestParams['REFRESH_ID'] ?? false,
        ];

        CRestCurrentUser::setDataExt($userAuth); // для работы с API в контексте текущего пользователя
        $currentUser = $this->CRestService->currentUser();

        if ($this->settings->isProduction()) {
            $isUserTehnolog = in_array($this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'], $currentUser['UF_DEPARTMENT']);
        } else {
            $isUserTehnolog = true;
            $dealId = 9;
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'deal-production-scheme.html.twig', [
            'isUserTehnolog' => $isUserTehnolog,
            'dealId' => $dealId,
            'isProduction' => $this->settings->isProduction(),
            'userAuth' => $userAuth
        ]);
    }
}
