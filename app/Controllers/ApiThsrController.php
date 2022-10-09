<?php

namespace App\Controllers;

use App\Models\THSRModel;
use Exception;

class ApiThsrController extends ApiBaseController
{
    // 載入模型
    function __construct()
    {
        try
        {
            $this->THSRModel = new THSRModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
        }
    }

    /**
     * 取得高鐵所有車站資料
     * 
     * 格式：/api/THSR/station
     * @return array 高鐵站資料陣列
     */
    function get_thsr_stations()
    {
        try
        {
            // 取得高鐵所有車站資料
            $stations = $this->THSRModel->get_stations()->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($stations);

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得高鐵指定起訖站時刻表資料
     * 
     * 格式：/api/THSR/arrival/from/{StationId}/to/{StationId}
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 起訖站時刻表資料
     */
    function get_thsr_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, 11) || !$this->validate_param("ToStationId", $toStationId, 11))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得行駛方向（0：南下；1：北上）
            $direction = 0;
            if (intval(str_replace("THSR-", "", $fromStationId)) > intval(str_replace("THSR-", "", $toStationId)))
            {
                $direction = 1;
            }

            // 取得指定高鐵行經起訖站的所有車次
            $trainIds = $this->THSRModel->get_trains_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();

            // 整理後的時刻表陣列
            $arrivals = [];

            // 透過列車代碼及起訖站來查詢時刻表
            for ($i = 0; $i < sizeof($trainIds); $i++)
            {
                $arrivalData = $this->THSRModel->get_arrivals($trainIds[$i]->HA_train_id, $fromStationId, $toStationId)->get()->getResult();
                
                if (sizeof($arrivalData) == 2)
                {
                    $arrivals[$i] = $arrivalData;
                }
            }

            // 重新排列時刻表資料
            $this->restructure_arrivals($arrivals);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得高鐵指定經緯度最近車站
     * 
     * 格式：/api/THSR/station/long/{Longitude}/lat/{Latitude}
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     * @param int $limit 回傳數量
     * @return array 最近高鐵站資料陣列
     */
    function get_thsr_nearest_station($longitude, $latitude, $limit = 1)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("Longitude", $longitude) || !$this->validate_param("Latitude", $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得高鐵所有車站資料
            $station = $this->THSRModel->get_nearest_station($longitude, $latitude, $limit)->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($station);

            // 回傳資料
            return $this->send_response($station);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
