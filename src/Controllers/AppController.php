<?php
namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
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
        $currentUser = $this->CRestService->currentUser();


        if ($this->settings->isProduction()) {
            $areUserTehnolog = in_array($this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'], $currentUser['UF_DEPARTMENT']);
        } else {
            $areUserTehnolog = true;
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'app.html.twig', [
            'areUserTehnolog' => $areUserTehnolog,
            'isProduction' => $this->settings->isProduction()
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

        $currentUser = $this->CRestService->currentUser();
        if ($this->settings->isProduction()) {
            $areUserTehnolog = in_array($this->settings->get('b24')['TEHNOLOG_DEPARTMENT_ID'], $currentUser['UF_DEPARTMENT']);
        } else {
            $areUserTehnolog = true;
        }

        $dealId = 9;
//        $result = $this->CRestService->callMethod('crm.deal.fields',[]);
//        $products = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $dealId]);
//        $response->getBody()->write('<pre>'. print_r($products, true). '</pre>');
//        return $response;

        $view = Twig::fromRequest($request);
        return $view->render($response, 'deal-production-scheme.html.twig', [
            'areUserTehnolog' => $areUserTehnolog,
            'dealId' => $dealId,
            'isProduction' => $this->settings->isProduction()
        ]);
    }
}
