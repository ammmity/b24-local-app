<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DealsController {
    const PRODUCTS_CATALOG_IBLOCK_ID = 14;
    const PRODUCTS_CATALOG_SECTION_ID = 13;
    const PRODUCT_PARTS_CATALOG_IBLOCK_ID = 14;
    const PRODUCT_PARTS_CATALOG_SECTION_ID = 15;
    const PRODUCT_PARTS_PROP_ID = 64;


    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
    )
    {}

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];

        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Не указан обязательный параметр id']));
            return $response;
        }

        $deal = null;
        $deal = $this->CRestService->callMethod('crm.deal.get', ['id' => $id])['result'] ?? null;
        if (empty($deal)) {
            $response->getBody()->write(json_encode(['error' => 'Сделка не найдена']));
            return $response;
        }

        $dealProducts = $this->CRestService->callMethod('crm.deal.productrows.get', ['id' => $id])['result'] ?? null;
        $deal['dealProducts'] = $dealProducts;

        $products = [];
        $productPartsRepository = $this->entityManager->getRepository(ProductPart::class);
        if ($dealProducts) {
            foreach ($dealProducts as $k => $dealProduct) {
                $productRaw = $this->CRestService->callMethod('catalog.product.get', ['id' => $dealProduct['PRODUCT_ID']]);
                $product = $productRaw['result']['product'];
                $deal['dealProducts'][$k]['product'] = $product;

                // Инициализируем массивы для частей
                $product['parts'] = [];
                $deal['dealProducts'][$k]['parts'] = [];

                // Проверяем наличие деталей продукта
                $productPartsPropertyId = 'property' . self::PRODUCT_PARTS_PROP_ID;
                if (isset($product[$productPartsPropertyId]) && is_array($product[$productPartsPropertyId])) {
                    $linkedProductPartIds = array_column($product[$productPartsPropertyId], 'value');

                    if (!empty($linkedProductPartIds)) {
                        $linkedProductParts = $productPartsRepository->findBy(['bitrix_id' => $linkedProductPartIds]);

                        foreach ($linkedProductParts as $linkedProductPart) {
                            $productPart = $linkedProductPart->toArray();
                            $product['parts'][] = $productPart;
                            $deal['dealProducts'][$k]['parts'][] = $productPart;
                        }
                    }
                }

                $products[] = $product;
            }
        }

        $response->getBody()->write(json_encode($deal));

        return $response;
    }
}
