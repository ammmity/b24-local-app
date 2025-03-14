<?php
namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AppController {
//    public const TEHNOLOG_DEPARTMENT_ID = 1;
    public const TEHNOLOG_DEPARTMENT_ID = 16;

    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager
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
        $areUserTehnolog = in_array(self::TEHNOLOG_DEPARTMENT_ID, $currentUser['UF_DEPARTMENT']);
//        $areUserTehnolog = true;
        $view = Twig::fromRequest($request);
        return $view->render($response, 'app.html.twig', [
            'areUserTehnolog' => $areUserTehnolog,
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
        $areUserTehnolog = in_array(self::TEHNOLOG_DEPARTMENT_ID, $currentUser['UF_DEPARTMENT']);
//        $areUserTehnolog = true;

//        $dealId = 9;
//        $result = $this->CRestService->callMethod('crm.deal.fields',[]);
//        $products = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $dealId]);
//        $response->getBody()->write('<pre>'. print_r($products, true). '</pre>');
//        return $response;

        $view = Twig::fromRequest($request);
        return $view->render($response, 'deal-production-scheme.html.twig', [
            'areUserTehnolog' => $areUserTehnolog,
            'dealId' => $dealId
        ]);
    }
}
