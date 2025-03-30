<?php

namespace App\Controllers;

use App\Entities\OperationPrice;
use App\Entities\OperationType;
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
        $operationPrices = $this->entityManager->getRepository(OperationPrice::class)->findAll();
        $result = [];
        foreach ($operationPrices as $operationPrice) {
            $result[] = $operationPrice->toArray();
        }

        $response->getBody()->write(json_encode($result));
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

        if (!isset($data['operation_type_id']) || !isset($data['price'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400);
        }

        $operationTypeId = (int)$data['operation_type_id'];
        $price = (int)$data['price'];

        $operationType = $this->entityManager->getRepository(OperationType::class)->find($operationTypeId);

        if (!$operationType) {
            $response->getBody()->write(json_encode(['error' => 'Operation type not found']));
            return $response->withStatus(404);
        }

        $operationPrice = new OperationPrice($operationType, $price);

        $this->entityManager->persist($operationPrice);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($operationPrice->toArray()));
        return $response->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $operationPrice = $this->entityManager->getRepository(OperationPrice::class)->find($id);

        if (!$operationPrice) {
            $response->getBody()->write(json_encode(['error' => 'Operation price not found']));
            return $response->withStatus(404);
        }

        if (isset($data['operation_type_id'])) {
            $operationTypeId = (int)$data['operation_type_id'];
            $operationType = $this->entityManager->getRepository(OperationType::class)->find($operationTypeId);

            if (!$operationType) {
                $response->getBody()->write(json_encode(['error' => 'Operation type not found']));
                return $response->withStatus(404);
            }

            $operationPrice->setOperationType($operationType);
        }

        if (isset($data['price'])) {
            $operationPrice->setPrice((int)$data['price']);
        }

        $this->entityManager->persist($operationPrice);
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
