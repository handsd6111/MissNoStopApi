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
            // 取得縣市
            $cities = $this->baseModel->get_cities()->get()->getResult();

            // 重新排列資料
            $this->restructure_cities($cities);

            // 回傳資料
            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
