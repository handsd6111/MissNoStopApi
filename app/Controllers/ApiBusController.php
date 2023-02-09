<?php

namespace App\Controllers;

use App\Controllers\ApiBusBaseController;
use App\Models\BusModel;
use Exception;

class ApiBusController extends ApiBusBaseController
{
    public $busModel;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->busModel = new BusModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }
    
    /**
     * /api/Bus/Route/{CityId}
     * 取得指定縣市的「公車路線」資料
     * @param string $cityId
     * @return mixed 公車路線資料
     */
    function get_bus_routes($cityId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("CityId", $cityId, parent::CITY_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $routes = $this->busModel->get_routes($cityId)->get()->getResult();

            if (sizeof($routes) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_routes($routes);

            $this->log_access_success();

            // 回傳資料
            return $this->send_response($routes);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([$e], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Bus/StationOfRoute/{RouteId}/{Direction}
     * 取得指定路線及行駛方向的「公車站」資料
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return mixed 公車站資料
     */
    function get_bus_stations($routeId, $direction)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::BUS_ROUTE_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->busModel->get_stations($routeId, $direction)->get()->getResult();

            if (sizeof($stations) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_stations($stations);

            $this->log_access_success();

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
     * /api/Bus/NearestStation/{Longitude}/{Latitude}
     * 取得指定經緯度的「最近公車站」資料
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @return mixed 最近公車站資料
     */
    function get_bus_nearest_station($longitude, $latitude)
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
            $stations = $this->busModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();

            if (sizeof($stations) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_nearest_stations($stations);

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
     * /api/Bus/Arrival/{FromStationId}/{ToStationId}/{Direction}
     * 取得指定起訖站及行駛方向的「公車時刻表」資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param int $direction 行駛方向
     * @return mixed 公車時刻表資料
     */
    function get_bus_arrivals($fromStationId, $toStationId, $direction)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::BUS_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::BUS_STATION_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $route = $this->busModel->get_route_by_station($fromStationId, $toStationId)->get()->getResult();

            if (!$route)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $arrivals = $this->busModel->get_arrivals($fromStationId, $toStationId, $direction)->get()->getResult();

            if (sizeof($arrivals) < 2)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals($arrivals);

            $this->log_access_success();

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Bus/ArrivalOfRoute/{RouteId}/{Direction}/{Time}
     * 取得指定路線、行駛方向及目前時間的「公車路線時刻表」資料
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @param string $time 目前時間
     * @return mixed 公車路線時刻表資料
     */
    function get_bus_arrivals_by_route($routeId, $direction, $time)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::BUS_ROUTE_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->busModel->get_arrivals_of_route($routeId, $direction, $time)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals_by_route($arrivals);

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
