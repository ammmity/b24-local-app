<?php
namespace App\Services;

use App\CRest\CRest;
class CRestService
{

    public function callMethod($method, $params = [])
    {
        return CRest::call($method, $params);
    }

    public function installApp($request)
    {
        return CRest::installApp($request);
    }

    public function addTask($params)
    {
        return $this->callMethod('tasks.task.add', $params)['result']['task'];
    }

    public function getTask(int $taskId, array $select = ['ID','TITLE'])
    {
        return $this->callMethod('tasks.task.get', [
            'taskId' => $taskId,
            'select' => $select
        ])['result']['task'];
    }

    public function updateTask($params)
    {
        return $this->callMethod('tasks.task.update', $params);
    }

    public function currentUser()
    {
        return $this->callMethod('user.current', [])['result'];
    }

    public function groups($params)
    {
        return $this->callMethod('socialnetwork.api.workgroup.list', $params)['result']['workgroups'];
    }

    public function addGroupStage($params)
    {
        return $this->callMethod('task.stages.add', $params)['result'];
    }

    public function eventBind(string $event,  string $handler)
    {
        return $this->callMethod('event.bind',
            [
                'event' => $event,
                'handler' => $handler
            ]
        );
    }

    /**
     * Получает стадии канбана
     *
     * @param string $groupId ID группы
     * @return array Массив стадий
     */
    public function kanbanStages($groupId)
    {
        return $this->callMethod('task.stages.get', [
            'entityId' => $groupId
        ])['result'];
    }

    // Создать документ прихода
    public function addCatalogDocument(string $title, string $comment, string $docType = 'A')
    {
        return $this->callMethod('catalog.document.add', [
            'fields' => [
                'docType' => $docType,
//                'contractorId' => '1',
//                'responsibleId' => '1',
//                'dateModify' => '2000-01-01T00:00:00+02:00',
//                'dateCreate' => '2000-01-01T00:00:00+02:00',
//                'createdBy' => '1',
//                'modifiedBy' => '1',
                'currency' => 'RUB',
                'status' => 'S',
//                'dateStatus' => '2000-01-01T00:00:00+02:00',
//                'dateDocument' => '2000-01-01T00:00:00+02:00',
//                'statusBy' => '1',
                'total' => '100',
                'commentary' => $comment,
                'title' => $title,
            ]
        ])['result'];
    }

    // Добавить товар в документ прихода
    public function addElementToCatalogDocument(
        int $documentId,
        int $storeFrom,
        int $storeTo,
        int $elementId,
        int $amount,
//        int $purchasingPrice,
    ) {
        return $this->callMethod('catalog.document.element.add', [
            'fields' => [
                'docId' => $documentId,
                'storeFrom' => $storeFrom,
                'storeTo' => $storeTo,
                'elementId' => $elementId,
                'amount' => $amount,
//                'purchasingPrice' => $purchasingPrice,
            ],
        ])['result'];
    }

    // Провести документ
    public function conductDocument(int $documentId) {
        return $this->callMethod('catalog.document.conduct', [
            'id' => $documentId,
        ])['result'];
    }
}
