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
