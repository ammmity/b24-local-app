<?php
namespace App\Services;

use App\CRest\CRest;
class CRestService
{

    public function callMethod($method, $params = [])
    {
        return CRest::call($method, $params);
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
}
