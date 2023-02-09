<?php

namespace App\Controllers;

use App\Controllers\ApiThsrBaseController;
use App\Models\THSRModel;
use Exception;

class ApiThsrController extends ApiThsrBaseController
{
    public $THSRModel;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->THSRModel = new THSRModel();
        }
        catch (Exception $e)
        {
            $this->log_access_fail($e);
        }
    }

    /**
     * /api/THSR/City
     * 取得「高鐵營運縣市」資料
     */
    function get_thsr_cities()
    {
        try
        {
            $cities = $this->THSRModel->get_thsr_cities()->get()->getResult();

            if (sizeof($cities) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_cities($cities);

            $this->log_access_success();

            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/THSR/Station
     * 取得「高鐵車站」資料
     * @return mixed 高鐵站資料
     */
    function get_thsr_stations()
    {
        try
        {
            $stations = $this->THSRModel->get_stations()->get()->getResult();

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
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/THSR/NearestStation/{Longitude}/{Latitude}
     * 取得指定經緯度的「最近高鐵車站」資料
     * @param string $longitude 經度（-180 ~ 180）
     * @param string $latitude 緯度（-90 ~ 90）
     * @return mixed 最近高鐵站資料
     */
    function get_thsr_nearest_station($longitude, $latitude)
    {
        try
        {
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->THSRModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();

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
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/THSR/Arrival/{FromStationId}/{ToStationId}
     * 取得指定起訖站的「高鐵時刻表」資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return mixed 高鐵時刻表資料
     */
    function get_thsr_arrivals($fromStationId, $toStationId)
    {
        try
        {
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
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/THSR/ArrivalOfTrain/{TrainId}
     * 取得指定車次的「高鐵車次時刻表」資料
     * @param string $trainId 車次代碼
     * @return mixed 高鐵車次時刻表資料
     */
    function get_thsr_arrivals_by_train($trainId)
    {
        try
        {
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
            $this->log_access_fail($e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
