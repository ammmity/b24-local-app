<?php
namespace App\Controllers;

use App\CRest\CRest;
use App\Services\CRestService;
use App\Settings\Settings;
use App\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DashboardController {
    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (empty($args['find'])) {
            $args['find'] = 'teh';
        }

        $filter = [];
        if (!empty($args['find'])) {
            $filter['FIND'] = "%{$args['find']}%";
        }

//        if (empty($filter)) {
//            // return error
//        }


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


        $response->getBody()->write('<pre>' . print_r($users, true) . '</pre>');
        return $response;
        $view = Twig::fromRequest($request);
        return $view->render($response, 'dashboard.html.twig');
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
        return $view->render($response, 'deal-detail.html.twig', [
            'deal' => $deal
        ]);
    }
}
