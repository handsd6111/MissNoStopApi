<?php

namespace App\Controllers;

use App\Controllers\ApiTraBaseController;
use App\Models\TRAModel;
use Exception;

class ApiTraController extends ApiTraBaseController
{
    public $TRAModel;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->TRAModel = new TRAModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    /**
     * /api/TRA/City
     * 取得「臺鐵營運縣市」資料
     * @return mixed 臺鐵營運縣市資料
     */
    function get_tra_cities()
    {
        try
        {
            $cities = $this->TRAModel->get_tra_cities()->get()->getResult();

            $this->restructure_cities($cities);

            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/TRA/StationOfCity/{CityId}
     * 取得指定縣市的「臺鐵車站」資料
     * @param string $cityId 縣市代碼
     * @return mixed 臺鐵車站資料
     */
    function get_stations_by_city($cityId)
    {
        try
        {
            if (!$this->validate_param("RouteId", $cityId, parent::CITY_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->TRAModel->get_stations_by_city($cityId)->get()->getResult();

            if (sizeof($stations) == 0)
            {
                return $this->send_response([], 400, lang("Exception.exception"));
            }
            $this->restructure_stations($stations);

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/TRA/NearestStation/{RouteId}/{Longitude}/{Latitude} 取得指定經緯度的「最近臺鐵車站」資料
     * @param string 經度
     * @param string 緯度
     * @return mixed 最近最近臺鐵車站資料
     */
    function get_tra_nearest_station($longitude, $latitude)
    {
        try
        {
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->TRAModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();

            if (sizeof($stations) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_stations($stations);

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/TRA/Arrival/{FromStationId}/{ToStationId}
     * 取得指定起訖站的「臺鐵時刻表」資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return mixed 臺鐵時刻表資料
     */
    function get_tra_arrivals($fromStationId, $toStationId)
    {
        try
        {
            if (!$this->validate_param("FromStationId", $fromStationId, parent::TRA_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::TRA_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->TRAModel->get_arrivals_by_stations($fromStationId, $toStationId)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals($arrivals, $fromStationId);

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/TRA/ArrivalOfTrain/{TrainId}
     * 取得指定車次的「臺鐵車次時刻表」資料
     * @param string $trainId 車次代碼
     * @return mixed 臺鐵車次時刻表資料
     */
    function get_tra_arrivals_by_train($trainId)
    {
        try
        {
            if (!$this->validate_param("TrainId", $trainId, parent::TRA_TRAIN_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->TRAModel->get_arrivals_by_train($trainId)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals_by_train($arrivals);
            
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
