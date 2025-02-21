<?php
namespace App\Services;

use App\CRest\CRest;

class CRestService
{
    public function callMethod($method, $params = [])
    {
        return CRest::call($method, $params);
    }
}
