<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use App\Models\THSRModel;
use Exception;

class ApiThsrController extends ApiBaseController
{
    public $THSRModel;

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

    function get_thsr_cities()
    {
        try
        {
            $cities = $this->THSRModel->get_thsr_cities()->get()->getResult();

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
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
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
            if (!$this->validate_param("FromStationId", $fromStationId, parent::THSR_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::THSR_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            $schedules = $this->THSRModel->get_arrivals_by_stations($fromStationId, $toStationId)->get()->getResult();

            $arrivals = [];

            $this->restructure_thsr_arrivals($schedules, $arrivals, $fromStationId);

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
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得高鐵所有車站資料
            $station = $this->THSRModel->get_nearest_station($longitude, $latitude)->get($limit)->getResult();

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
