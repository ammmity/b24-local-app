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
}
