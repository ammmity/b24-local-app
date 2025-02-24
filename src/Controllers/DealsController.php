<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DealsController {
    const PRODUCTS_CATALOG_IBLOCK_ID = 15;
    const PRODUCTS_CATALOG_SECTION_ID = 11;
    const PRODUCT_PARTS_CATALOG_IBLOCK_ID = 15;
    const PRODUCT_PARTS_CATALOG_SECTION_ID = 9;
    const PRODUCT_PARTS_PROP_ID = 53;


    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
    )
    {}

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];

        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Parameter id is required']));
            return $response;
        }

        $deal = null;
        $products = null;
        $deal = $this->CRestService->callMethod('crm.deal.get', ['id' => $id])['result'] ?? null;
        if (empty($deal)) {
            $response->getBody()->write(json_encode(['error' => 'Deal not exists']));
            return $response;
        }

        $dealProducts = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $id])['result'] ?? null;
        $deal['dealProducts'] = $dealProducts;

        $products = [];
        if ($dealProducts) {
            foreach ($dealProducts as $k => $dealProduct) {
                $productRaw = $this->CRestService->callMethod('catalog.product.get', ['id' => $dealProduct['PRODUCT_ID']]);
                $product = isset($productRaw['result']) ? $productRaw['result']['product'] : null;

//                if (isset($product['parentId'])) { // Если товар унаследован - возьмем детали из корневого товара TODO: wtf... найти решение
//                    $parentProductRaw = $this->CRestService->callMethod('catalog.product.get', ['id' => $product['parentId']['value']]);
//                    $parentProduct = isset($parentProductRaw['result']) ? $parentProductRaw['result']['product'] : null;
//                    if (isset($parentProduct['property'.self::PRODUCT_PARTS_PROP_ID])) {
//                        $product['property'.self::PRODUCT_PARTS_PROP_ID] = $parentProduct['property'.self::PRODUCT_PARTS_PROP_ID];
//                    }
//                }

                // детали продукта
                $isProductExistsAndProductPartsFilled =
                    $product
                    && isset($product['property'.self::PRODUCT_PARTS_PROP_ID]);
                if ($isProductExistsAndProductPartsFilled) {
                    $linkedProductParts = $product['property'.self::PRODUCT_PARTS_PROP_ID];
                    foreach ($linkedProductParts as $linkedProductPart) {
                        $product['parts'][] = $this->CRestService->callMethod('catalog.product.get', ['id' => $linkedProductPart['value']])['result']['product']['name'];;
                    }
                }
//                else {
//                    continue;
//                }
                $products[] = $product;
            }
        }


        $response->getBody()->write(json_encode([
            'deal' => $deal,
            'products' => $products,
        ]));

        return $response;
    }
}
