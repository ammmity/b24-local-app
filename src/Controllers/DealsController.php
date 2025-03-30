<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Entities\Good;
use App\Entities\GoodPart;
use App\Services\CRestService;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DealsController {

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected SettingsInterface $settings
    )
    {}

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $b24Settings = $this->settings->get('b24');
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

                $withParentId = isset($product['parentId']);
                if ($withParentId) { // Если товар унаследован - возьмем детали из корневого товара TODO: wtf... найти решение
                    $parentProductRaw = $this->CRestService->callMethod('catalog.product.get', ['id' => $product['parentId']['value']]);
                    $parentProduct = isset($parentProductRaw['result']) ? $parentProductRaw['result']['product'] : null;
                    if (isset($parentProduct['property'.$b24Settings['PRODUCT_PARTS_PROP_ID']])) {
                        $product['property'.$b24Settings['PRODUCT_PARTS_PROP_ID']] = $parentProduct['property'.$b24Settings['PRODUCT_PARTS_PROP_ID']];
                    }
                }

                $deal['dealProducts'][$k]['product'] = $product;

                // Инициализируем массивы для частей
                $product['parts'] = [];
                $deal['dealProducts'][$k]['parts'] = [];

                // Проверяем наличие деталей продукта
                $productPartsPropertyId = 'property' . $b24Settings['PRODUCT_PARTS_PROP_ID'];
                if (isset($product[$productPartsPropertyId]) && is_array($product[$productPartsPropertyId])) {
                    $linkedProductPartIds = array_column($product[$productPartsPropertyId], 'value');

                    if (!empty($linkedProductPartIds)) {
                        $linkedProductParts = $productPartsRepository->findBy(['bitrix_id' => $linkedProductPartIds]);

                        // Получаем Good по bitrix_id
                        $good = $this->entityManager->getRepository(Good::class)->findOneBy([
                            'bitrix_id' => $withParentId ? $product['parentId']['value'] : $product['id']
                        ]);

                        // Получаем количество товара в сделке
                        $dealProductQuantity = isset($dealProduct['QUANTITY']) ? (int)$dealProduct['QUANTITY'] : 1;

                        if ($good) {
                            foreach ($linkedProductParts as $linkedProductPart) {
                                $productPart = $linkedProductPart->toArray();

                                // Ищем GoodPart для текущей детали
                                $goodPart = $this->entityManager->getRepository(GoodPart::class)->findOneBy([
                                    'good' => $good,
                                    'productPart' => $linkedProductPart
                                ]);

                                // Добавляем информацию о количестве из GoodPart
                                if ($goodPart) {
                                    // Умножаем количество деталей на количество товара в сделке
                                    $productPart['quantity'] = $goodPart->getQuantity() * $dealProductQuantity;
                                } else {
                                    $productPart['quantity'] = 0;
                                }

                                $product['parts'][] = $productPart;
                                $deal['dealProducts'][$k]['parts'][] = $productPart;
                            }
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
