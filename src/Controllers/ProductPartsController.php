<?php

namespace App\Controllers;

use App\Entities\ProductPart;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductPartsController {

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
