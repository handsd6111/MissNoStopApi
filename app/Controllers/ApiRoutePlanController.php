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
     * @param string $fromTransportName 起站運輸工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toTransportName 訖站運輸工具名稱
     * @param string $toStationId 訖站代碼
     * @return array 路線資料
     */
    function route_plan($fromTransportName, $fromStationId, $toTransportName, $toStationId, $startTime)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_transport_param($fromTransportName, "FromStationId", $fromStationId)
                || !$this->validate_transport_param($toTransportName, "ToStationId", $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得起訖站所屬路線
            $fromRoute = $this->get_route_by_station($fromTransportName, $fromStationId);
            $toRoute   = $this->get_route_by_station($toTransportName, $toStationId);

            // 若起訖站屬同一交通工具且署同意條路線則使用 ApiController 的 Api 即可
            if ($fromTransportName == $toTransportName && $fromRoute == $toRoute)
            {
                $arrival = $this->get_arrival($fromTransportName, $fromStationId, $toStationId, $startTime);
            }

            return $this->send_response($arrival);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }
}
