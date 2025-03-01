<?php
namespace App\Controllers;

use App\Entities\BitrixGroupKanbanStage;
use App\Entities\OperationType;
use App\Services\CRestService;
use App\Services\KanbanStageService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class OperationTypesController {
    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected KanbanStageService $kanbanStageService
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $operationTypesRepository = $this->entityManager->getRepository(OperationType::class);

        if (!empty($queryParams['name'])) {
            $queryBuilder = $operationTypesRepository->createQueryBuilder('p');
            $queryBuilder
                ->where('p.name LIKE :name')
                ->setParameter('name', '%' . $queryParams['name'] . '%');
            $operationTypes = $queryBuilder->getQuery()->getResult();
        } else {
            $operationTypes = $operationTypesRepository->findAll();
        }

        if (!empty($operationTypes)) {
            $operationTypes = array_map(fn($operationType) => [
                'id' => $operationType->getId(),
                'name' => $operationType->getName(),
                'machine' => $operationType->getMachine(),
                'bitrix_group_id' => $operationType->getBitrixGroupId(),
            ], $operationTypes);
        }

        $response->getBody()->write(json_encode($operationTypes));

        return $response;
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Parameter id is required']));
            return $response;
        }

        $operationType = $this->entityManager->getRepository(OperationType::class)->find($id);
        if (!$operationType) {
            $response->getBody()->write(json_encode(['error' => 'OperationType not found']));
            return $response;
        }

        $response->getBody()->write(json_encode($operationType->toArray()));

        return $response;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (
            empty($data['name'])
            || empty($data['machine'])
        ) {
            $response->getBody()->write(json_encode(['error' => 'Обязательные поля: name,machine']));
            return $response->withStatus(400);
        }

        $operationType = new OperationType($data['name'], $data['machine'], $data['bitrix_group_id']);

        $this->entityManager->persist($operationType);
        $this->entityManager->flush();

        // Создание стадий для канбана группы
        $stages = $this->kanbanStageService->getOrCreateStages($data['bitrix_group_id']);
        // Сохраним связь в бд
        foreach ($stages as $stage) {
            $bitrixGroupKanbanRepository = $this->entityManager->getRepository(BitrixGroupKanbanStage::class);
            $isStageExists = $bitrixGroupKanbanRepository->findOneBy([
                'bitrix_group_id' => $data['bitrix_group_id'],
                'stage_id' => $stage['id']
            ]);

            if (!$isStageExists) {
                $newStage = new BitrixGroupKanbanStage(
                    (string) $data['bitrix_group_id'],
                    (string) $stage['id'],
                    (string) $stage['title'],
                );
                $this->entityManager->persist($newStage);
            }
        }

        $this->entityManager->flush();


        $response->getBody()->write(json_encode($operationType->toArray()));
        return $response;
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (
            empty($data['name'])
            || empty($data['machine'])
        ) {
            $response->getBody()->write(json_encode(['error' => 'Required fields: name,machine']));
            return $response;
        }

        $operationType = new OperationType($data['name'], $data['machine'], $data['bitrix_group_id']);
        $this->entityManager->persist($operationType);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($operationType->toArray()));
        return $response;
    }

    public function remove(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        if (empty($id)) {
            $response->getBody()->write(json_encode(['error' => 'Parameter id is required']));
            return $response;
        }

        $operationType = $this->entityManager->getRepository(OperationType::class)->find($id);

        if (!$operationType) {
            $response->getBody()->write(json_encode(['error' => 'OperationType not found']));
            return $response;
        }

        $this->entityManager->remove($operationType);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode(true));
        return $response;
    }
}
