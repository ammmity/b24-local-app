<?php
namespace App\Services;

use App\CRest\CRest;
use App\CRest\CRestCurrentUser;

class CRestService
{

    public function callMethod($method, $params = [])
    {
        return CRest::call($method, $params);
    }

    public function callMethodAsCurrentUser($method, $params = [])
    {
        return CRestCurrentUser::call($method, $params);
    }

    public function currentUser()
    {
        return $this->callMethodAsCurrentUser('user.current', [])['result'];
    }

    public function installApp($request)
    {
        return CRest::installApp($request);
    }

    public function addTask($params)
    {
        $result = $this->callMethod('tasks.task.add', $params);
//        $logData = print_r(['rs' => $result, 'params' => $params], 1);
//        $logDir = dirname(__DIR__, 2) . '/logs';
//        if (!is_dir($logDir)) {
//            mkdir($logDir, 0755, true);
//        }
//        file_put_contents($logDir . '/'.__METHOD__.'.log', $logData, FILE_APPEND);

        return $result['result']['task'];
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

    public function groups($params)
    {
        return $this->callMethod('socialnetwork.api.workgroup.list', $params)['result']['workgroups'];
    }

    public function addGroupStage($params)
    {
        return $this->callMethodAsCurrentUser('task.stages.add', $params)['result'];
    }

    public function removeGroupStage($params)
    {
        return $this->callMethodAsCurrentUser('task.stages.delete', $params)['result'];
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

    public function getGroupUsers($groupId)
    {
        return $this->callMethod('sonet_group.user.get', [
            'ID' => $groupId
        ])['result'];
    }

    /**
     * Получает стадии канбана
     *
     * @param string $groupId ID группы
     * @return array Массив стадий
     */
    public function kanbanStages($groupId)
    {
        return $this->callMethodAsCurrentUser('task.stages.get', [
            'entityId' => $groupId
        ])['result'];
    }

    // Создать документ прихода
    public function addCatalogDocument(string $title, string $comment, string $docType = 'S')
    {
        return $this->callMethod('catalog.document.add', [
            'fields' => [
                'docType' => $docType,
                'contractorId' => '1',
                'responsibleId' => '1',
                'currency' => 'RUB',
                'total' => '0',
                'commentary' => $comment,
                'title' => $title,
            ]
        ])['result']['document'];
    }

    // Добавить товар в документ прихода
    public function addElementToCatalogDocument(
        int $documentId,
        int $storeFrom,
        int $storeTo,
        int $elementId,
        int $amount,
        int $purchasingPrice = 0,
        int $basePrice = 0
    ) {
        return $this->callMethod('catalog.document.element.add', [
            'fields' => [
                'docId' => $documentId,
                'storeFrom' => $storeFrom,
                'storeTo' => $storeTo,
                'elementId' => $elementId,
                'amount' => $amount,
                'purchasingPrice' => $purchasingPrice,
            ],
        ])['result'];
    }

    // Провести документ
    public function conductDocument(int $documentId) {
        return $this->callMethod('catalog.document.conduct', [
            'id' => $documentId,
        ]);
    }
}
