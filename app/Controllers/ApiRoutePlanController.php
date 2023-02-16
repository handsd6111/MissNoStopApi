<?php

namespace App\Controllers;

use App\Controllers\ApiRoutePlanBaseController;
use Exception;

/**
 * 路線規劃 API 控制器
 */
class ApiRoutePlanController extends ApiRoutePlanBaseController
{
    /**
     * /api/Metro/RoutePlan/{FromStationId}/{ToStationId}/{StartTime}
     * 取得指定起訖站及發車時間的「捷運路線規劃」資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $departureTime 發車時間
     * @return array 捷運路線規劃資料
     */
    function metro_route_plan($fromStationId, $toStationId, $departureTime)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::METRO_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::METRO_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $routePlan = $this->get_route_plan($fromStationId, $toStationId, $departureTime);

            return $this->send_response($routePlan);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response($e, 500, lang("Exception.exception"));
        }
    }
}
