<?php

namespace App\Controllers;

use App\Entities\ProductionScheme;
use App\Entities\ProductionSchemeStage;
use App\Entities\ProductPart;
use App\Entities\OperationType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductionSchemesController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function get(Request $request, Response $response, array $args): Response
    {
        $dealId = $args['id'] ?? null;

        if (!$dealId) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        $scheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $dealId]);

        if (!$scheme) {
            $response->getBody()->write(json_encode(null));
            return $response;
        }

        // Принудительно инициализируем коллекцию этапов
        // $scheme->getStages()->toArray();
        
        // Получаем свежие данные
        // $this->entityManager->refresh($scheme);

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['deal_id'])) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response;
        }

        // Проверяем, существует ли уже схема для данной сделки
        $existingScheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $data['deal_id']]);

        if ($existingScheme) {
            $response->getBody()->write(json_encode(
                ['error' => 'Production scheme already exists for this deal']
            ));
            return $response;
        }

        $scheme = new ProductionScheme($data['deal_id']);

        if (isset($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $stageData) {
                if (!$this->validateStageData($stageData)) {
                    $response->getBody()->write(json_encode(['error' => 'Invalid stage data']));
                    return $response;
                }

                $productPart = $this->entityManager->getRepository(ProductPart::class)
                    ->find($stageData['product_part_id']);
                $operationType = $this->entityManager->getRepository(OperationType::class)
                    ->find($stageData['operation_type_id']);

                if (!$productPart || !$operationType) {
                    $response->getBody()->write(json_encode(
                        ['error' => 'Product part or operation type not found']
                    ));
                    return $response;
                }

                $stage = new ProductionSchemeStage(
                    $scheme,
                    $productPart,
                    $operationType,
                    $stageData['stage_number'],
                    $stageData['quantity']
                );

                if (isset($stageData['executor_id'])) {
                    $stage->setExecutorId($stageData['executor_id']);
                }
                if (isset($stageData['bitrix_task_id'])) {
                    $stage->setBitrixTaskId($stageData['bitrix_task_id']);
                }

                $scheme->addStage($stage);
            }
        }

        $this->entityManager->persist($scheme);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $dealId = $args['id'] ?? null;

        if (!$dealId) {
            $response->getBody()->write(json_encode(['error' => 'deal_id is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $scheme = $this->entityManager->getRepository(ProductionScheme::class)
            ->findOneBy(['dealId' => $dealId]);

        if (!$scheme) {
            $response->getBody()->write(json_encode(['error' => 'Scheme not found']));
            return $response;
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (isset($data['status']) && $data['status'] === 'progress') {
            // Проверяем, что все этапы имеют исполнителей
            foreach ($scheme->getStages() as $stage) {
                if (!$stage->getExecutorId()) {
                    $response->getBody()->write(json_encode([
                        'error' => 'Невозможно запустить производство: не все этапы имеют исполнителей'
                    ]));
                    return $response;
                }
            }
        }

        if (isset($data['status'])) {
            $scheme->setStatus($data['status']);
        }

        if (isset($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $stageData) {
                $stage = $this->entityManager->getRepository(ProductionSchemeStage::class)
                    ->findOneBy([
                        'scheme' => $scheme,
                        'productPart' => $stageData['product_part_id'],
                        'operationType' => $stageData['operation_type_id']
                    ]);

                if ($stage) {
                    // Обновляем существующий этап
                    if (isset($stageData['executor_id'])) {
                        $stage->setExecutorId($stageData['executor_id']);
                    }
                    if (isset($stageData['bitrix_task_id'])) {
                        $stage->setBitrixTaskId($stageData['bitrix_task_id']);
                    }
                    if (isset($stageData['status'])) {
                        $stage->setStatus($stageData['status']);
                    }
                    if (isset($stageData['quantity'])) {
                        $stage->setQuantity($stageData['quantity']);
                    }
                    if (isset($stageData['bitrix_task_id'])) {
                        $stage->setBitrixTaskId($stageData['bitrix_task_id']);
                    }
                }
            }
        }

        $this->entityManager->flush();
        
        // Получаем свежие данные после обновления
        $this->entityManager->refresh($scheme);
        
        // Принудительно инициализируем коллекцию этапов
        $scheme->getStages()->toArray();

        $response->getBody()->write(json_encode($scheme->toArray()));
        return $response;
    }

    private function validateStageData(array $data): bool
    {
        $requiredFields = ['product_part_id', 'operation_type_id', 'stage_number', 'quantity'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }
}
