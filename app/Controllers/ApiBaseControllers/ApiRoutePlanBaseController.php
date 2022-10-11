<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\ApiBaseControllers\ApiBaseController;
use Exception;

/**
 * 路線規劃 API 底層控制器
 */
class ApiRoutePlanBaseController extends ApiBaseController
{
    protected $transportNames = [
        "BUS",
        "METRO",
        "THSR",
        "TRA"
    ];

    /**
     * 驗證運輸工具與車站參數
     * @param string &$transportName 運輸工具名稱
     * @param string $stationName 車站參數名稱
     * @param string $stationId 車站參數
     * @return bool 驗證結果
     */
    protected function validate_transport_param(&$transportName, $stationName, $stationId)
    {
        try
        {
            // 轉大寫
            $transportName = strtoupper($transportName);

            // 檢查運輸工具名稱是否可辨認
            if (!in_array($transportName, $this->transportNames))
            {
                $this->validateErrMsg = lang("RoutePlan.transportNameNotFound");
                return false;
            }

            // 取得車站代碼限制長度
            $stationIdLength = $this->get_station_id_length($transportName);

            // 驗證車站參數
            if (!$this->validate_param($stationName, $stationId, $stationIdLength))
            {
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得車站代碼限制長度
     * @param string $transportName 運輸工具名稱
     * @return int 代碼限制長度
     */
    protected function get_station_id_length($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return BUS_STATION_ID_LENGTH;
                    break;
                case "METRO":
                    return METRO_STATION_ID_LENGTH;
                    break;
                case "THSR":
                    return THSR_STATION_ID_LENGTH;
                    break;
                case "TRA":
                    return TRA_STATION_ID_LENGTH;
                    break;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
