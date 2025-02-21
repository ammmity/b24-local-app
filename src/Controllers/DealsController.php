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

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
//        $queryParams = $request->getQueryParams();
//        $productPartsRepository = $this->entityManager->getRepository(ProductPart::class);
//
//        if (!empty($queryParams['name'])) {
//            $queryBuilder = $productPartsRepository->createQueryBuilder('p');
//            $queryBuilder
//                ->where('p.name LIKE :name')
//                ->setParameter('name', '%' . $queryParams['name'] . '%');
//            $productParts = $queryBuilder->getQuery()->getResult();
//        } else {
//            $productParts = $productPartsRepository->findAll();
//        }
//
//        if (!empty($productParts)) {
//            $productParts = array_map(fn($productPart) => [
//                'id' => $productPart->getId(),
//                'name' => $productPart->getName(),
//                'xml_id' => $productPart->getXmlId(),
//                'bitrix_id' => $productPart->getBitrixId()
//            ], $productParts);
//        }
//
//        $response->getBody()->write(json_encode($productParts));
//
//        return $response;
    }

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

    public function import(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $productListResponse = $this->CRestService->callMethod('catalog.product.list', [
            'select' => [
                "id",
                "iblockId",
                "name",
                "code",
                "type",
                "xmlId",
                "property".self::PRODUCT_PARTS_PROP_ID,
            ],
            'filter' => [
                "iblockId" => self::PRODUCT_PARTS_CATALOG_IBLOCK_ID,
                "iblockSectionId" => self::PRODUCT_PARTS_CATALOG_SECTION_ID,
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
