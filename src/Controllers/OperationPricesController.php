<?php

namespace App\Controllers;

use App\Entities\OperationPrice;
use App\Entities\OperationType;
use App\Entities\ProductPart;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OperationPricesController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {}

    public function list(Request $request, Response $response): Response
    {
        $prices = $this->entityManager->getRepository(OperationPrice::class)->findAll();
        $data = array_map(fn(OperationPrice $price) => $price->toArray(), $prices);

        $response->getBody()->write(json_encode($data));
        return $response;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $operationPrice = $this->entityManager->getRepository(OperationPrice::class)->find($id);

        if (!$operationPrice) {
            $response->getBody()->write(json_encode(['error' => 'Operation price not found']));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($operationPrice->toArray()));
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Проверяем обязательные поля
        if (!isset($data['operation_type_id']) || !isset($data['price'])) {
            $response->getBody()->write(json_encode(['error' => 'Не указаны обязательные поля'] ));
            return $response->withStatus(400);
        }

        // Получаем тип операции
        $operationType = $this->entityManager->find(OperationType::class, $data['operation_type_id']);
        if (!$operationType) {
            $response->getBody()->write(json_encode(['error' => 'Тип операции не найден']));
            return $response;
        }

        // Получаем деталь, если указана
        $productPart = null;
        if (isset($data['product_part_id'])) {
            $productPart = $this->entityManager->find(ProductPart::class, $data['product_part_id']);
            if (!$productPart) {
                $response->getBody()->write(json_encode(['error' => 'Деталь не найдена']));
                return $response;
            }
        }

        // Проверяем уникальность комбинации
        $existingPrice = $this->entityManager->getRepository(OperationPrice::class)->findOneBy([
            'operationType' => $operationType,
            'productPart' => $productPart
        ]);

        if ($existingPrice) {
            $response->getBody()->write(json_encode(['error' => 'Цена для этой комбинации операции и детали уже существует']));
            return $response->withStatus(422);
        }

        // Создаем новую цену
        $price = new OperationPrice(
            $operationType,
            $data['price'],
            $productPart
        );

        $this->entityManager->persist($price);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($price->toArray()));
        return $response->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $operationPrice = $this->entityManager->getRepository(OperationPrice::class)->find($id);

        if (isset($data['operation_type_id'])) {
            $operationType = $this->entityManager->find(OperationType::class, $data['operation_type_id']);
            if (!$operationType) {
                return $response->withStatus(404)->withJson([
                    'error' => 'Тип операции не найден'
                ]);
            }
            $operationPrice->setOperationType($operationType);
        }

        // Обработка детали
        if (array_key_exists('product_part_id', $data)) {
            $productPart = null;
            if ($data['product_part_id'] !== null) {
                $productPart = $this->entityManager->find(ProductPart::class, $data['product_part_id']);
                if (!$productPart) {
                    return $response->withStatus(404)->withJson([
                        'error' => 'Деталь не найдена'
                    ]);
                }
            }

            // Проверяем уникальность комбинации
            $existingPrice = $this->entityManager->getRepository(OperationPrice::class)->findOneBy([
                'operationType' => $operationPrice->getOperationType(),
                'productPart' => $productPart
            ]);

            if ($existingPrice && $existingPrice->getId() !== $operationPrice->getId()) {
                return $response->withStatus(422)->withJson([
                    'error' => 'Цена для этой комбинации операции и детали уже существует'
                ]);
            }

            $operationPrice->setProductPart($productPart);
        }

        if (isset($data['price'])) {
            $operationPrice->setPrice($data['price']);
        }

        $this->entityManager->flush();

        $response->getBody()->write(json_encode($operationPrice->toArray()));
        return $response;
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $operationPrice = $this->entityManager->getRepository(OperationPrice::class)->find($id);

        if (!$operationPrice) {
            $response->getBody()->write(json_encode(['error' => 'Operation price not found']));
            return $response->withStatus(404);
        }

        $this->entityManager->remove($operationPrice);
        $this->entityManager->flush();

        return $response->withStatus(204);
    }
}
