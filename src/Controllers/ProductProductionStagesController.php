<?php

namespace App\Controllers;

use App\Entities\OperationType;
use App\Entities\ProductPart;
use App\Entities\ProductProductionStage;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductProductionStagesController
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function index(Request $request, Response $response): Response
    {
        $productPartId = $request->getQueryParams()['product_part_id'] ?? null;

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('pps')
            ->from(ProductProductionStage::class, 'pps');

        if ($productPartId) {
            $queryBuilder
                ->where('pps.productPart = :productPartId')
                ->setParameter('productPartId', $productPartId);
        }

        $stages = $queryBuilder
            ->orderBy('pps.order', 'ASC')
            ->getQuery()
            ->getResult();

        $response->getBody()->write(json_encode(array_map(
            fn(ProductProductionStage $stage) => $stage->toArray(),
            $stages
        )));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            $productPart = $this->entityManager->find(ProductPart::class, $data['product_part_id']);
            if (!$productPart) {
                throw new \Exception('Продукт не найден');
            }

            $operationType = $this->entityManager->find(OperationType::class, $data['operation_type_id']);
            if (!$operationType) {
                throw new \Exception('Тип операции не найден');
            }

            // Получаем максимальный order для данного продукта
            $maxOrder = $this->entityManager->createQueryBuilder()
                ->select('MAX(pps.order)')
                ->from(ProductProductionStage::class, 'pps')
                ->where('pps.productPart = :productPartId')
                ->setParameter('productPartId', $productPart->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $newOrder = ($maxOrder ?? 0) + 1;

            $stage = new ProductProductionStage(
                $productPart,
                $operationType,
                $data['stage'],
                $newOrder
            );

            $this->entityManager->persist($stage);
            $this->entityManager->flush();

            $response->getBody()->write(json_encode($stage->toArray()));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $stage = $this->entityManager->find(ProductProductionStage::class, $args['id']);
            if (!$stage) {
                throw new \Exception('Этап производства не найден');
            }

            $data = json_decode($request->getBody()->getContents(), true);

            if (isset($data['operation_type_id'])) {
                $operationType = $this->entityManager->find(OperationType::class, $data['operation_type_id']);
                if (!$operationType) {
                    throw new \Exception('Тип операции не найден');
                }
                $stage->setOperationType($operationType);
            }

            if (isset($data['stage'])) {
                $stage->setStage($data['stage']);
            }

            if (isset($data['order'])) {
                $stage->setOrder($data['order']);
            }

            $this->entityManager->flush();

            $response->getBody()->write(json_encode($stage->toArray()));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $stage = $this->entityManager->find(ProductProductionStage::class, $args['id']);
            if (!$stage) {
                throw new \Exception('Этап производства не найден');
            }

            $this->entityManager->remove($stage);
            $this->entityManager->flush();

            return $response->withStatus(204);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function reorder(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Начинаем транзакцию для обеспечения целостности данных
            $this->entityManager->beginTransaction();
            
            foreach ($data['stages'] as $stageData) {
                $stage = $this->entityManager->find(ProductProductionStage::class, $stageData['id']);
                if ($stage) {
                    $stage->setOrder($stageData['order']);
                }
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            return $response->withStatus(200);
            
        } catch (\Exception $e) {
            // Откатываем транзакцию в случае ошибки
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400);
        }
    }
} 