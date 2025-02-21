<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DealsController {

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
    )
    {}

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $deal = null;
        $products = null;

        if (!empty($id)) {
            $dealRaw = $this->CRestService->callMethod('crm.deal.get', ['id' => $id]);
            $productsRaw = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $id]);
            $deal = $dealRaw['result'] ?? null;
            $products = $productsRaw['result'] ?? null;
        }


        $response->getBody()->write(json_encode([
            'deal' => $deal,
            'products' => $products,
        ]));

        return $response;
    }
}
