<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use Exception;

class ApiGeneralController extends ApiBaseController
{
    /**
     * 取得縣市資料
     * 
     * 格式：/api/General/City
     * @return array 縣市資料陣列
     */
    function get_cities()
    {
        try
        {
            $cities = $this->baseModel->get_cities()->get()->getResult();

            foreach ($cities as $i => $city)
            {
                $cities[$i] = [
                    "CityId" => $city->id,
                    "CityName" => [
                        "TC" => $city->name_TC,
                        "EN" => $city->name_EN
                    ]
                ];
            }
            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
