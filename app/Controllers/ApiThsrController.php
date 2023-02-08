<?php

namespace App\Controllers;

use App\Controllers\ApiThsrBaseController;
use App\Models\THSRModel;
use Exception;

class ApiThsrController extends ApiThsrBaseController
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
            log_message("critical", $e);
        }
    }

    function get_thsr_cities()
    {
        try
        {
            $cities = $this->THSRModel->get_thsr_cities()->get()->getResult();

            $this->restructure_cities($cities);

            $this->log_access_success();

            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
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
            $stations = $this->THSRModel->get_stations()->get()->getResult();

            $this->restructure_stations($stations);
            
            $this->log_access_success();
            
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
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
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->THSRModel->get_nearest_station($longitude, $latitude)->get($limit)->getResult();

            if (sizeof($stations) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_stations($stations);
            
            $this->log_access_success();

            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得高鐵指定起訖站時刻表資料
     * 
     * 格式：/api/THSR/Arrival/{FromStationId}/{ToStationId}
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
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->THSRModel->get_arrivals_by_stations($fromStationId, $toStationId)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals($arrivals, $fromStationId);
            
            $this->log_access_success();

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    function get_thsr_arrivals_by_train($trainId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("TrainId", $trainId, parent::THSR_TRAIN_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->THSRModel->get_arrivals_by_train($trainId)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals_by_train($arrivals);
            
            $this->log_access_success();

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
