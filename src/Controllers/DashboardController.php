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

class DashboardController {
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


//
//        if (empty($args['find'])) {
//            $args['find'] = 'teh';
//        }
//
//        $filter = [];
//        if (!empty($args['find'])) {
//            $filter['FIND'] = "%{$args['find']}%";
//        }

//        if (empty($filter)) {
//            // return error
//        }

//
//        $users = $this->CRestService->callMethod('user.search',
//            [
//                'filter' => $filter
//            ]
//        )['result'];
//
//        if (!empty($users)) {
//            $users = array_map(fn($user) => [
//                'id' => $user['ID'],
//                'name' => $user['NAME'],
//                'last_name' => $user['LAST_NAME']
//            ], $users);
//        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'app.html.twig');
    }

    public function deal(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $requestParams = $request->getParsedBody();
        $deal = null;

        if (!empty($requestParams['PLACEMENT_OPTIONS'])) {
            $dealId = json_decode($requestParams['PLACEMENT_OPTIONS'], true)['ID'];
            if (!empty($dealId)) {
                $deal = ['id' => $dealId];
            }
        }

        if (!empty($queryParams['id'])) {
            $deal = ['id' => $queryParams['id']];
        }

//        $result = $this->CRestService->callMethod('crm.deal.fields',[]);
//        $products = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $dealId]);
//        $response->getBody()->write('<pre>'. print_r($products, true). '</pre>');
//        return $response;

        $view = Twig::fromRequest($request);
        return $view->render($response, 'deal.html.twig', [
            'deal' => $deal
        ]);
    }
}
