<?php

namespace App\Controllers;

use App\Entities\OperationType;
use App\Entities\ProductPart;
use App\Entities\ProductProductionStage;
use App\Entities\VirtualPart;
use App\Services\CRestService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductProductionStagesController
{
    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager
    )
    {}

    public function list(Request $request, Response $response): Response
    {
        $productPartId = $request->getQueryParams()['product_part_id'] ?? null;

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('pps')
            ->from(ProductProductionStage::class, 'pps')
            ->orderBy('pps.stage', 'ASC');

        if ($productPartId) {
            $queryBuilder
                ->where('pps.productPart = :productPartId')
                ->setParameter('productPartId', $productPartId);
        }

        $stages = $queryBuilder
            ->getQuery()
            ->getResult();

        $response->getBody()->write(json_encode(array_map(
            fn(ProductProductionStage $stage) => $stage->toArray(),
            $stages
        )));

        return $response;
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

            // Получаем максимальный stage для данного продукта
            $maxStage = $this->entityManager->createQueryBuilder()
                ->select('MAX(pps.stage)')
                ->from(ProductProductionStage::class, 'pps')
                ->where('pps.productPart = :productPartId')
                ->setParameter('productPartId', $productPart->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $newStage = ($maxStage ?? 0) + 1;

            $stage = new ProductProductionStage(
                $productPart,
                $operationType,
                $newStage
            );
            
            // Добавляем обработку result_id
            if (isset($data['result_id']) && $data['result_id'] !== null) {
                $virtualPart = $this->entityManager->find(VirtualPart::class, $data['result_id']);
                if ($virtualPart) {
                    $stage->setResult($virtualPart);
                }
            }

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

            // Исправляем обработку result_id
            if (array_key_exists('result_id', $data)) {
                if ($data['result_id'] === null) {
                    $stage->setResult(null);
                } else {
                    $virtualPart = $this->entityManager->find(VirtualPart::class, $data['result_id']);
                    if (!$virtualPart) {
                        throw new \Exception('Виртуальная деталь не найдена');
                    }
                    $stage->setResult($virtualPart);
                }
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
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new \InvalidArgumentException('Invalid request data structure');
        }

        try {
            $this->entityManager->beginTransaction();

            foreach ($data['items'] as $item) {
                if (!isset($item['id']) || !isset($item['stage'])) {
                    continue;
                }

                $stage = $this->entityManager->find(ProductProductionStage::class, $item['id']);
                if (!$stage) {
                    continue;
                }

                $stage->setStage($item['stage']);
                $this->entityManager->persist($stage);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $response->getBody()->write(json_encode(true));
            return $response;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
