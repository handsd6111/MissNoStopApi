<?php

namespace App\Controllers;

use App\Controllers\ApiBaseControllers\ApiRoutePlanBaseController;
use Exception;

/**
 * 路線規劃 API 控制器
 */
class ApiRoutePlanController extends ApiRoutePlanBaseController
{
    /**
     * 規劃路線
     * @param string $transportName 運輸工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toTransportName 訖站運輸工具名稱
     * @param string $toStationId 訖站代碼
     * @return array 路線資料
     */
    function route_plan($transportName, $fromStationId, $toStationId, $departureTime)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_transport_param($transportName, "FromStationId", $fromStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            $routePlan = $this->get_route_plan($transportName, $fromStationId, $toStationId, $departureTime);

            return $this->send_response($routePlan);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }
}
