<?php

namespace App\Controllers;

use App\Entities\OperationLog;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OperationLogsController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function list(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        // Создаем базовый запрос
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('ol')
            ->from(OperationLog::class, 'ol');

        // Применяем фильтр по пользователю
        if (isset($queryParams['userId']) && $queryParams['userId']) {
            $queryBuilder->andWhere('ol.userId = :userId')
                ->setParameter('userId', (int)$queryParams['userId']);
        }

        // Применяем фильтр по периоду
        if (isset($queryParams['startDate']) && $queryParams['startDate']) {
            $startDate = new DateTime($queryParams['startDate']);
            $startDate->setTime(0, 0, 0);

            $queryBuilder->andWhere('ol.createdDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (isset($queryParams['endDate']) && $queryParams['endDate']) {
            $endDate = new DateTime($queryParams['endDate']);
            $endDate->setTime(23, 59, 59);

            $queryBuilder->andWhere('ol.createdDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        // Сортировка и лимит
        $queryBuilder->orderBy('ol.createdDate', 'DESC');

        // Если нет фильтров, ограничиваем выборку 100 записями
        if (!isset($queryParams['userId']) && !isset($queryParams['startDate']) && !isset($queryParams['endDate'])) {
            $queryBuilder->setMaxResults(100);
        }

        $operationLogs = $queryBuilder->getQuery()->getResult();

        $result = [];
        foreach ($operationLogs as $operationLog) {
            $result[] = $operationLog->toArray();
        }

        $response->getBody()->write(json_encode($result));
        return $response;
    }

    // Метод для получения списка уникальных пользователей
    public function getUsers(Request $request, Response $response, array $args): Response
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('DISTINCT o.userId, o.username')
                     ->from(OperationLog::class, 'o')
                     ->orderBy('o.username', 'ASC');

        $query = $queryBuilder->getQuery();
        $users = $query->getResult();

        $result = array_map(function ($user) {
            return [
                'userId' => $user['userId'],
                'username' => $user['username']
            ];
        }, $users);


        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $operationLog = $this->entityManager->getRepository(OperationLog::class)->find($id);

        if (!$operationLog) {
            $response->getBody()->write(json_encode(['error' => 'Operation log not found']));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($operationLog->toArray()));
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['task_link']) ||
            !isset($data['bitrix_task_id']) ||
            !isset($data['deal_id']) ||
            !isset($data['detail_id']) ||
            !isset($data['detail_name']) ||
            !isset($data['quantity']) ||
            !isset($data['username']) ||
            !isset($data['user_id']) ||
            !isset($data['price']) ||
            !isset($data['operation'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400);
        }

        $operationLog = new OperationLog(
            $data['task_link'],
            (int)$data['bitrix_task_id'],
            (int)$data['deal_id'],
            (int)$data['detail_id'],
            $data['detail_name'],
            (int)$data['quantity'],
            $data['username'],
            (int)$data['user_id'],
            (int)$data['price'],
            $data['operation']
        );

        $this->entityManager->persist($operationLog);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode($operationLog->toArray()));
        return $response->withStatus(201);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $operationLog = $this->entityManager->getRepository(OperationLog::class)->find($id);

        if (!$operationLog) {
            $response->getBody()->write(json_encode(['error' => 'Operation log not found']));
            return $response->withStatus(404);
        }

        $this->entityManager->remove($operationLog);
        $this->entityManager->flush();

        return $response->withStatus(204);
    }

    public function getByDealId(Request $request, Response $response, array $args): Response
    {
        $dealId = (int)$args['dealId'];

        $operationLogs = $this->entityManager->getRepository(OperationLog::class)->findBy(['dealId' => $dealId]);

        $result = [];
        foreach ($operationLogs as $operationLog) {
            $result[] = $operationLog->toArray();
        }

        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
