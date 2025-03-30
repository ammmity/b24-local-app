<?php

namespace App\Controllers;

use App\Entities\Good;
use App\Entities\GoodPart;
use App\Entities\ProductPart;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CRestService;

class GoodsController
{

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected CRestService $CRestService,
        protected SettingsInterface $settings
    ) {}

    public function list(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $goodsRepository = $this->entityManager->getRepository(Good::class);

        if (!empty($queryParams['name'])) {
            $queryBuilder = $goodsRepository->createQueryBuilder('g');
            $queryBuilder
                ->where('g.name LIKE :name')
                ->setParameter('name', '%' . $queryParams['name'] . '%');
            $goods = $queryBuilder->getQuery()->getResult();
        } else {
            $goods = $goodsRepository->findAll();
        }

        $data = array_map(fn(Good $good) => $good->toArray(), $goods);

        $response->getBody()->write(json_encode($data));
        return $response;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $good = $this->entityManager->getRepository(Good::class)->find($args['id']);

        if (!$good) {
            $response->getBody()->write(json_encode(['error' => 'Товар не найден']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($good->toArray()));
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        try {
            $good = new Good();
            $good->setName($data['name']);
            $good->setXmlId($data['xml_id']);
            $good->setBitrixId($data['bitrix_id']);

            // Если переданы детали, добавляем их
            if (isset($data['parts']) && is_array($data['parts'])) {
                foreach ($data['parts'] as $partData) {
                    $productPart = $this->entityManager
                        ->getRepository(ProductPart::class)
                        ->find($partData['product_part_id']);

                    if (!$productPart) {
                        throw new \Exception("Деталь с ID {$partData['product_part_id']} не найдена");
                    }

                    $goodPart = new GoodPart();
                    $goodPart->setProductPart($productPart);
                    $goodPart->setQuantity($partData['quantity']);
                    $good->addPart($goodPart);
                }
            }

            $this->entityManager->persist($good);
            $this->entityManager->flush();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $good->toArray()
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $good = $this->entityManager->getRepository(Good::class)->find($args['id']);

        if (!$good) {
            $response->getBody()->write(json_encode(['error' => 'Товар не найден']));
            return $response;
        }

        $data = json_decode($request->getBody()->getContents(), true);

        try {
            if (isset($data['name'])) {
                $good->setName($data['name']);
            }
            if (isset($data['xml_id'])) {
                $good->setXmlId($data['xml_id']);
            }
            if (isset($data['bitrix_id'])) {
                $good->setBitrixId($data['bitrix_id']);
            }

            // Обновляем детали если они переданы
            if (isset($data['parts']) && is_array($data['parts'])) {
                // Удаляем старые связи
                foreach ($good->getParts() as $part) {
                    $good->removePart($part);
                    $this->entityManager->remove($part);
                }

                // Добавляем новые
               foreach ($data['parts'] as $partData) {
                   $productPart = $this->entityManager
                       ->getRepository(ProductPart::class)
                       ->find($partData['product_part_id']);

                   if (!$productPart) {
                       throw new \Exception("Деталь с ID {$partData['product_part_id']} не найдена");
                   }

                   $goodPart = new GoodPart();
                   $goodPart->setProductPart($productPart);
                   $goodPart->setQuantity($partData['quantity']);
                   $good->addPart($goodPart);
               }
            }

            $this->entityManager->flush();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $good->toArray()
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $good = $this->entityManager->getRepository(Good::class)->find($args['id']);

        if (!$good) {
            $response->getBody()->write(json_encode(['error' => 'Товар не найден']));
            return $response;
        }

        try {
            $this->entityManager->remove($good);
            $this->entityManager->flush();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Товар успешно удален'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function import(Request $request, Response $response, array $args): Response
    {
        $b24Settings = $this->settings->get('b24');
        $productListResponse = $this->CRestService->callMethod('catalog.product.list', [
            'select' => [
                "id",
                "iblockId",
                "name",
                "code",
                "type",
                "xmlId",
                "property".$b24Settings['PRODUCT_PARTS_PROP_ID'],
            ],
            'filter' => [
                "iblockId" => $b24Settings['PRODUCTS_CATALOG_IBLOCK_ID'],
                "iblockSectionId" => $b24Settings['PRODUCTS_CATALOG_SECTION_ID'],
            ],
            'order' => [
                "id" => "desc",
            ]
        ]);

        $products = $productListResponse['result']['products'];

        // $response->getBody()->write(json_encode($products));
        // return $response;

        foreach ($products as $productData) {
            $good = $this->entityManager->getRepository(Good::class)->findOneBy(['bitrix_id' => $productData['id']]);

            if (!$good) {
                // Создаем новый товар
                $good = new Good();
                $good->setName($productData['name']);
                $good->setXmlId($productData['xmlId']);
                $good->setBitrixId($productData['id']);
                $this->entityManager->persist($good);
            }

            // Получаем текущие связи товара
            $existingGoodParts = $this->entityManager->getRepository(GoodPart::class)->findBy([
                'good' => $good
            ]);

            // Создаем массив ID деталей, которые пришли из API
            $incomingPartIds = [];
            if (isset($productData['property'.$b24Settings['PRODUCT_PARTS_PROP_ID']])) {
                foreach ($productData['property'.$b24Settings['PRODUCT_PARTS_PROP_ID']] as $partData) {
                    $productPart = $this->entityManager->getRepository(ProductPart::class)->findOneBy([
                        'bitrix_id' => $partData['value']
                    ]);

                    if ($productPart) {
                        $incomingPartIds[] = $productPart->getId();

                        // Проверяем, существует ли уже связь
                        $existingGoodPart = $this->entityManager->getRepository(GoodPart::class)->findOneBy([
                            'good' => $good,
                            'productPart' => $productPart
                        ]);

                        if (!$existingGoodPart) {
                            // Создаем новую связь
                            $goodPart = new GoodPart();
                            $goodPart->setGood($good);
                            $goodPart->setProductPart($productPart);
                            $goodPart->setQuantity(0); // Количество при импорте не указывается.
                            $this->entityManager->persist($goodPart);
                        }
                    }
                }
            }

            // Удаляем связи, которых нет в пришедших данных
            foreach ($existingGoodParts as $existingGoodPart) {
                if (!in_array($existingGoodPart->getProductPart()->getId(), $incomingPartIds)) {
                    $this->entityManager->remove($existingGoodPart);
                }
            }
        }

        $this->entityManager->flush();

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Импорт товаров завершен']));
        return $response;
    }
}
