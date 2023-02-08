<?php

namespace App\Controllers;

use App\Controllers\ApiMetroBaseController;
use App\Models\MetroModel;
use Exception;

class ApiMetroController extends ApiMetroBaseController
{
    public $metroModel;

    // 載入模型
    function __construct()
    {
        try
        {
            $this->metroModel = new MetroModel();
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 取得「捷運系統」資料
     * 
     * 格式：/api/Metro/System
     * @return array 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            // 取得捷運系統
            $systems = $this->metroModel->get_systems()->get()->getResult();

            // 重新排列資料
            $this->restructure_systems($systems);

            $this->log_access_success();

            // 回傳資料
            return $this->send_response($systems);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 取得指定系統的「捷運路線」資料
     * 
     * 格式：/api/Metro/Route/{SystemId}
     * @param string $systemId 捷運系統
     * @return array 路線資料陣列
     */
    function get_metro_routes($systemId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("SystemId", $systemId, parent::METRO_SYSTEM_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $routes = $this->metroModel->get_routes($systemId)->get()->getResult();

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
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 取得指定路線的「捷運站」資料
     * 
     * 格式：/api/Metro/StationOfRoute/{RouteId}
     * @param string $routeId 路線代碼
     * @return array 車站資料陣列
     */
    function get_metro_stations($routeId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::METRO_ROUTE_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $stations = $this->metroModel->get_stations($routeId)->get()->getResult();

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
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 取得指定路線及經緯度的「最近捷運站」資料
     * 
     * 格式：/api/Metro/NearestStation/{Longitude}/{Latitude}
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     * @param int $limit 回傳數量
     */
    function get_metro_nearest_station($longitude, $latitude)
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
            $stations = $this->metroModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();
            
            if (sizeof($stations) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_nearest_stations($stations);

            $this->log_access_success();

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 取得指定起訖站的「捷運時刻表」資料
     * 
     * 格式：/api/Metro/Arrival/{FromStationId}/{ToStationId}
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 時刻表資料陣列
     */
    function get_metro_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::METRO_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::METRO_STATION_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            // 取得行駛方向
            $direction = $this->get_direction($fromStationId, $toStationId);
            // 取得起訖站皆行經的所有捷運子路線
            $subRoutes = $this->get_sub_routes_by_stations($fromStationId, $toStationId, $direction);
            // 取得指定子路線的總運行時間
            $durations = $this->get_durations($fromStationId, $toStationId, $subRoutes, $direction);
            // 取得時刻表資料
            $arrivals = $this->get_arrivals($fromStationId, $direction, $subRoutes, $durations);

            if (sizeof($arrivals) == 0)
            {
                $this->log_access_fail();

                return $this->send_response([], 400, lang("Query.resultNotFound"));
            }
            $this->restructure_arrivals($arrivals);

            $this->log_access_success();

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    function get_metro_arrivals_by_route($routeId, $direction, $time)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::METRO_ROUTE_ID_LENGTH))
            {
                $this->log_validate_fail();

                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->metroModel->get_arrivals_by_route($routeId, $direction, $time)->get()->getResult();

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
            return $this->get_caught_exception($e);
        }
    }
}
