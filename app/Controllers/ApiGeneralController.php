<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use Exception;

class ApiGeneralController extends ApiBaseController
{
    /**
     * /api/General/City
     * 取得縣市資料陣列
     * @return mixed 縣市資料陣列
     */
    function get_cities()
    {
        try
        {
            $cities = $this->baseModel->get_cities()->get()->getResult();

            foreach ($cities as $i => $city)
            {
                $cities[$i] = [
                    "CityId" => $city->city_id,
                    "CityName" => [
                        "TC" => $city->name_TC,
                        "EN" => $city->name_EN
                    ]
                ];
            }
            $this->log_access_success();

            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
