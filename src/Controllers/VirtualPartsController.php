<?php

namespace App\Controllers;

use App\Entities\VirtualPart;
use App\Services\CRestService;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VirtualPartsController
{

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected CRestService $CRestService,
        protected SettingsInterface $settings
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        $virtualParts = $this->entityManager->getRepository(VirtualPart::class)->findAll();
        $result = [];

        foreach ($virtualParts as $virtualPart) {
            $result[] = $virtualPart->toArray();
        }

        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID не указан']));
            return $response;
        }

        $virtualPart = $this->entityManager->getRepository(VirtualPart::class)->find($id);
        if (!$virtualPart) {
            $response->getBody()->write(json_encode(['error' => 'Виртуальная деталь не найдена']));
            return $response;
        }

        $response->getBody()->write(json_encode($virtualPart->toArray()));
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['name']) || !isset($data['bitrix_id'])) {
            $response->getBody()->write(json_encode(['error' => 'Не указаны обязательные поля']));
            return $response;
        }

        // Проверка на существование детали с таким bitrix_id
        $existingPart = $this->entityManager->getRepository(VirtualPart::class)->findOneBy([
            'bitrix_id' => $data['bitrix_id']
        ]);

        if ($existingPart) {
            $response->getBody()->write(json_encode(['error' => 'Виртуальная деталь с таким bitrix_id уже существует']));
            return $response;
        }

        $virtualPart = new VirtualPart($data['name'], $data['bitrix_id']);

        $this->entityManager->persist($virtualPart);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($virtualPart->toArray()));
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID не указан']));
            return $response;
        }

        $virtualPart = $this->entityManager->getRepository(VirtualPart::class)->find($id);
        if (!$virtualPart) {
            $response->getBody()->write(json_encode(['error' => 'Виртуальная деталь не найдена']));
            return $response;
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (isset($data['name'])) {
            $virtualPart->setName($data['name']);
        }

        if (isset($data['bitrix_id'])) {
            // Проверка на существование другой детали с таким же bitrix_id
            $existingPart = $this->entityManager->getRepository(VirtualPart::class)->findOneBy([
                'bitrix_id' => $data['bitrix_id']
            ]);

            if ($existingPart && $existingPart->getId() !== $virtualPart->getId()) {
                $response->getBody()->write(json_encode(['error' => 'Другая виртуальная деталь с таким bitrix_id уже существует']));
                return $response;
            }

            $virtualPart->setBitrixId($data['bitrix_id']);
        }

        $this->entityManager->flush();

        $response->getBody()->write(json_encode($virtualPart->toArray()));
        return $response;
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID не указан']));
            return $response;
        }

        $virtualPart = $this->entityManager->getRepository(VirtualPart::class)->find($id);
        if (!$virtualPart) {
            $response->getBody()->write(json_encode(['error' => 'Виртуальная деталь не найдена']));
            return $response;
        }

        $this->entityManager->remove($virtualPart);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode(['success' => true]));
        return $response;
    }

    public function import(Request $request, Response $response): Response
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
            ],
            'filter' => [
                "iblockId" => $b24Settings['VIRTUAL_PARTS_CATALOG_IBLOCK_ID'],
                "iblockSectionId" => $b24Settings['VIRTUAL_PARTS_CATALOG_SECTION_ID'],
            ],
            'order' => [
                "id" => "desc",
            ]
        ]);

        $products = $productListResponse['result']['products'];

        foreach ($products as $productData) {
            $virtualPart = $this->entityManager->getRepository(VirtualPart::class)->findOneBy(['bitrix_id' => $productData['id']]);

            if (!$virtualPart) {
                // Создаем новую виртуальную деталь
                $virtualPart = new VirtualPart();
                $virtualPart->setName($productData['name']);
                $virtualPart->setBitrixId($productData['id']);
                $this->entityManager->persist($virtualPart);
            }
        }

        $this->entityManager->flush();

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Импорт товаров завершен']));
        return $response;
    }
}
