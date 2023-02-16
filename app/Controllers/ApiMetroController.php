<?php

namespace App\Controllers;

use App\Controllers\ApiMetroBaseController;
use App\Models\MetroModel;
use Exception;

class ApiMetroController extends ApiMetroBaseController
{
    public $metroModel;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->metroModel = new MetroModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Metro/System
     * 取得「捷運系統」資料
     * @return mixed 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            $systems = $this->metroModel->get_systems()->get()->getResult();
            
            $this->restructure_systems($systems);

            return $this->send_response($systems);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Metro/Route/{SystemId}
     * 取得指定系統的「捷運路線」資料
     * @param string $systemId 捷運系統
     * @return mixed 捷運路線資料
     */
    function get_metro_routes($systemId)
    {
        try
        {
            if (!$this->validate_param("SystemId", $systemId, parent::METRO_SYSTEM_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $routes = $this->metroModel->get_routes($systemId)->get()->getResult();

            if (sizeof($routes) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_routes($routes);

            return $this->send_response($routes);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Metro/StationOfRoute/{RouteId}
     * 取得指定系統及路線的「捷運站」資料
     * @param string $routeId 路線代碼
     * @return mixed 捷運站資料
     */
    function get_metro_stations($routeId)
    {
        try
        {
            if (!$this->validate_param("RouteId", $routeId, parent::METRO_ROUTE_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->metroModel->get_stations($routeId)->get()->getResult();

            if (sizeof($stations) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_stations($stations);

            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Metro/NearestStation/{Longitude}/{Latitude}
     * 取得指定經緯度的「最近捷運站」資料
     * @param string $longitude 經度（-180 ~ 180）
     * @param string $latitude 緯度（-90 ~ 90）
     * @return mixed 最近捷運站資料
     */
    function get_metro_nearest_station($longitude, $latitude)
    {
        try
        {
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->metroModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();
            
            if (sizeof($stations) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_nearest_stations($stations);

            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * /api/Metro/Arrival/{FromStationId}/{ToStationId}
     * 取得指定起訖站的「捷運時刻表」資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return mixed 捷運時刻表陣列
     */
    function get_metro_arrivals($fromStationId, $toStationId)
    {
        try
        {
            if (!$this->validate_param("FromStationId", $fromStationId, parent::METRO_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::METRO_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $direction = $this->get_direction($fromStationId, $toStationId);
            
            $subRoutes = $this->get_sub_routes_by_stations($fromStationId, $toStationId, $direction);
            
            $durations = $this->get_durations($fromStationId, $toStationId, $subRoutes, $direction);
            
            $arrivals = $this->get_arrivals($fromStationId, $direction, $subRoutes, $durations);

            if (sizeof($arrivals) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals($arrivals);

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }


    /**
     * /api/Metro/ArrivalOfRoute/{RouteId}/{Direction}/{Time}
     * 取得指定路線、行駛方向及目前時間的「捷運路線時刻表」資料
     * @param string $routeId 路線代碼
     * @param string $direction 行駛方向
     * @param string $time 目前時間
     * @return mixed 捷運路線時刻表陣列
     */
    function get_metro_arrivals_by_route($routeId, $direction, $time)
    {
        try
        {
            if (!$this->validate_param("RouteId", $routeId, parent::METRO_ROUTE_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->metroModel->get_arrivals_by_route($routeId, $direction, $time)->get()->getResult();

            if (sizeof($arrivals) == 0)
            {
                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals_by_route($arrivals);

            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
