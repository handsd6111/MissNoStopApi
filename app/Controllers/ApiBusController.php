<?php

namespace App\Controllers\ApiBaseControllers;

use App\Models\BusModel;
use Exception;

class ApiBusController extends ApiBaseController
{
    // 載入模型
    function __construct()
    {
        try
        {
            $this->busModel = new BusModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
        }
    }
    
    /**
     * 取得指定公車縣市的所有路線資料
     * @param string $cityId 縣市代碼
     * @return array 公車路線資料
     */
    function get_bus_routes($cityId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("CityId", $cityId, parent::CITY_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車縣市的所有路線資料
            $routes = $this->busModel->get_routes($cityId)->get()->getResult();

            // 重新排列公車路線資料
            $this->restructure_routes($routes);

            // 回傳資料
            return $this->send_response($routes);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([$e], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定公車路線與行駛方向的所有車站資料
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return array 公車站資料
     */
    function get_bus_stations($routeId, $direction)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::BUS_ROUTE_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車路線的所有車站資料
            $stations = $this->busModel->get_stations($routeId, $direction)->get()->getResult();

            // 重新排列公車站資料
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
     * 取得指定公車路線及經緯度的最近車站資料
     * @param string $routeId 路線代碼
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @param int $limit 回傳數量
     * @return array 公車站資料
     */
    function get_bus_nearest_station($routeId, $longitude, $latitude, $limit = 1)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::BUS_ROUTE_ID_LENGTH)
                || !$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車路線及經緯度的最近車站資料
            $station = $this->busModel->get_nearest_station($routeId, $longitude, $latitude)->get($limit)->getResult();

            // 重新排列公車站資料
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

    /**
     * 取得指定公車路線、行駛方向及起訖站的時刻表
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @return array 時刻表資料
     */
    function get_bus_arrivals($route, $direction, $fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::BUS_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::BUS_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 起站時刻表
            $fromArrivals = $this->busModel->get_arrivals($route, $direction, $fromStationId)->get()->getResult();

            // 起站時刻表
            $toArrivals = $this->busModel->get_arrivals($route, $direction, $toStationId)->get()->getResult();

            if (!sizeof($fromArrivals))
            {
                return $this->send_response(["stationId" => $fromStationId], 400, lang("Query.stationNotFound"));
            }
            if (!sizeof($toArrivals))
            {
                return $this->send_response(["stationId" => $toStationId], 400, lang("Query.stationNotFound"));
            }

            $arrivals = [];

            // 重新排序時刻表資料
            $this->restructure_bus_arrivals($arrivals, $fromArrivals, $toArrivals);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
