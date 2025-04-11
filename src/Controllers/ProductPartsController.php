<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Entities\GoodPart;
use App\Services\CRestService;
use App\Settings\SettingsInterface;
use App\Entities\ProductionSchemeStage;
use App\Entities\ProductionScheme;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductPartsController {

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected SettingsInterface $settings
    )
    {}

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $productPartsRepository = $this->entityManager->getRepository(ProductPart::class);

        if (!empty($queryParams['name'])) {
            $queryBuilder = $productPartsRepository->createQueryBuilder('p');
            $queryBuilder
                ->where('p.name LIKE :name')
                ->setParameter('name', '%' . $queryParams['name'] . '%');
            $productParts = $queryBuilder->getQuery()->getResult();
        } else {
            $productParts = $productPartsRepository->findAll();
        }

        if (!empty($productParts)) {
            $productParts = array_map(fn($productPart) => [
                'id' => $productPart->getId(),
                'name' => $productPart->getName(),
                'xml_id' => $productPart->getXmlId(),
                'bitrix_id' => $productPart->getBitrixId()
            ], $productParts);
        }

        $response->getBody()->write(json_encode($productParts));

        return $response;
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = [];
        $id = $args['id'];
        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Parameter id is required']));
            return $response;
        }

        $productPart = $this->entityManager->getRepository(ProductPart::class)->find($id);
        if (!$productPart) {
            $response->getBody()->write(json_encode(['error' => 'ProductPart not found']));
            return $response;
        }

        $response->getBody()->write(json_encode($productPart->toArray()));

        return $response;
    }

    public function delete(RequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $productPart = $this->entityManager->getRepository(ProductPart::class)->find($args['id']);

        if (!$productPart) {
            $response->getBody()->write(json_encode(['error' => 'Деталь не найдена']));
            return $response->withStatus(404);
        }

        // Проверяем, используется ли деталь в других таблицах
        $isUsed = $this->entityManager->getRepository(GoodPart::class)
            ->findOneBy(['productPart' => $productPart]);

        if ($isUsed) {
            $response->getBody()->write(json_encode([
                'error' => 'Невозможно удалить деталь, так как она используется в товарах. Сначала удалите все связи с товарами.'
            ]));
            return $response->withStatus(400);
        }

        // Проверяем использование в активных схемах производства
        $isUsedInSchemes = $this->entityManager->getRepository(ProductionSchemeStage::class)
            ->createQueryBuilder('stage')
            ->select('COUNT(stage)')
            ->join('stage.scheme', 'ps')
            ->where('stage.productPart = :productPart')
            ->andWhere('ps.status != :status')
            ->setParameter('productPart', $productPart)
            ->setParameter('status', 'done')
            ->getQuery()
            ->getSingleScalarResult() > 0;

        if ($isUsedInSchemes) {
            $response->getBody()->write(json_encode([
                'error' => 'Невозможно удалить деталь, так как она используется в активных схемах производства.'
            ]));
            return $response->withStatus(400);
        }

        try {
            // Обновляем ссылки на деталь в завершенных схемах
            $this->entityManager->createQueryBuilder()
                ->update(ProductionSchemeStage::class, 'stage')
                ->set('stage.productPart', ':null')
                ->where('stage.productPart = :productPart')
                ->andWhere('EXISTS (
                    SELECT 1 
                    FROM App\Entities\ProductionScheme ps 
                    WHERE ps = stage.scheme 
                    AND ps.status = :status
                )')
                ->setParameter('null', null)
                ->setParameter('productPart', $productPart)
                ->setParameter('status', 'done')
                ->getQuery()
                ->execute();

            // Удаляем деталь
            $this->entityManager->remove($productPart);
            $this->entityManager->flush();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Деталь успешно удалена'
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Ошибка при удалении детали.' 
            ]));
            return $response->withStatus(400);
        }
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
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
                "iblockId" => $b24Settings['PRODUCT_PARTS_CATALOG_IBLOCK_ID'],
                "iblockSectionId" => $b24Settings['PRODUCT_PARTS_CATALOG_SECTION_ID'],
            ],
            'order' => [
                "id" => "desc",
            ]
        ]);

        $payload = [
            'items' => $productListResponse['result']['products'],
            'total' => $productListResponse['total']
        ];

        if (!empty($productListResponse['result']['products'])) {
            $productPartsRepository = $this->entityManager->getRepository(ProductPart::class);

            $this->entityManager->beginTransaction();

            try {
                foreach ($productListResponse['result']['products'] as $productPartElement) {
                    $productPart = $productPartsRepository->findOneBy(['bitrix_id' => $productPartElement['id']]);

                    if ($productPart) {
                        $productPart->setName($productPartElement['name']);
                        $productPart->setBitrixId($productPartElement['xmlId']);
                    } else {
                        $productPart = new ProductPart();
                        $productPart->setName($productPartElement['name']);
                        $productPart->setXmlId($productPartElement['xmlId']);
                        $productPart->setBitrixId($productPartElement['id']);
                        $this->entityManager->persist($productPart);
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            }
        }

        $response->getBody()->write(json_encode(true));

        return $response;
    }
}
