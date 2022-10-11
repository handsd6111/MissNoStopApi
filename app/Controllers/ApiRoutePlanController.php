<?php

namespace App\Controllers;

use App\Controllers\ApiBaseControllers\ApiMetroBaseController;
use App\Controllers\ApiBaseControllers\ApiRoutePlanBaseController;
use App\Models\MetroModel;
use Exception;

/**
 * 路線規劃 API 控制器
 */
class ApiRoutePlanController extends ApiRoutePlanBaseController
{
    /**
     * 載入模型
     */
    protected function __construct()
    {
        try
        {
            $this->metroModel      = new MetroModel();
            $this->metroController = new ApiMetroBaseController();
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 規劃路線
     * @param string $fromTransportName 起站運輸工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toTransportName 訖站運輸工具名稱
     * @param string $toStationId 訖站代碼
     * @return array 路線資料
     */
    protected function routePlan($fromTransportName, $fromStationId, $toTransportName, $toStationId, $startTime)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_transport_param($fromTransportName, "FromStationId", $fromStationId)
                || $this->validate_transport_param($toTransportName, "ToStationId", $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }
}
