<?php

namespace App\Controllers;

use App\Models\MetroModel;
use Exception;

class ApiMetroController extends ApiBaseController
{
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
            if (!$this->validate_param("SystemId", $systemId, 4))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            //取得路線
            $routes = $this->metroModel->get_routes($systemId)->get()->getResult();

            // 重新排列資料
            $this->restructure_routes($routes);

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
            if (!$this->validate_param("RouteId", $routeId, 12))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得捷運站
            $stations = $this->metroModel->get_stations($routeId)->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($stations);
            
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
     * 格式：/api/Metro/NearestStation/{RouteId}/{Longitude}/{Latitude}
     * @param string $routeId 捷運路線代碼
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     * @param int $limit 回傳數量
     */
    function get_metro_nearest_station($routeId, $longitude, $latitude, $limit = 1)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, 12) || !$this->validate_param("Longitude", $longitude) || !$this->validate_param("Latitude", $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得最近捷運站
            $station = $this->metroModel->get_nearest_station($routeId, $longitude, $latitude, $limit)->get()->getResult();
            
            // 重新排列資料
            $this->restructure_stations($station);

            // 回傳資料
            return $this->send_response($station);
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
            if (!$this->validate_param("FromStationId", $fromStationId) || !$this->validate_param("ToStationId", $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得起訖站序號
            $fromSeq   = $this->get_sequence($fromStationId);
            $toSeq     = $this->get_sequence($toStationId);

            // 取得行駛方向
            $direction = $this->get_direction($fromSeq, $toSeq);

            // 取得起訖站皆行經的所有捷運路線
            $routes    = $this->get_routes_by_stations($fromStationId, $toStationId);

            $arrivals  = [];

            // 取得每條路線的時刻表
            for ($i = 0; $i < sizeof($routes); $i++)
            {
                $arrivals[$i] = $this->get_arrival($fromSeq, $toSeq, $routes[$i], $direction);
            }

            // 重新排列資料
            $this->restructure_metro_arrivals($arrivals, $fromStationId, $toStationId);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 重新排列捷運時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return void 不回傳值
     */
    function restructure_metro_arrivals(&$arrivals, $fromStationId, $toStationId)
    {
        try
        {
            if (!sizeof($arrivals))
            {
                return;
            }
            foreach ($arrivals as $index1 => $arrival)
            {
                $schedules = [];
                foreach ($arrival as $index2 => $schedule)
                {
                    $schedules[$index2] = [
                        "Sequence"      => $schedule->sequence,
                        "DepartureTime" => $schedule->departure_time,
                        "ArrivalTime"   => $schedule->arrival_time
                    ];
                }
                $arrivals[$index1] = [
                    "RouteId"       => $arrival[0]->route_id,
                    "FromStationId" => $fromStationId,
                    "ToStationId"   => $toStationId,
                    "Schedules"     => $schedules
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得起訖站皆行經的路線資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param array 路線資料
     * @throws Exception 查無資料
     */
    function get_routes_by_stations($fromStationId, $toStationId)
    {
        try
        {
            $routes = $this->metroModel->get_routes_by_stations($fromStationId, $toStationId)->get()->getResult();
            if (!$routes)
            {
                throw new Exception(lang("Query.resultNotFOund"), 1);
            }
            for ($i = 0; $i < sizeof($routes); $i++)
            {
                $routes[$i] = $routes[$i]->route_id;
            }
            return $routes;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運站的序號
     * @param string $stationId 捷運站代碼
     * @return int 捷運站序號
     * @throws Exception 查無資料
     */
    function get_sequence($stationId)
    {
        try
        {
            $sequence = $this->metroModel->get_station_sequence($stationId)->get()->getResult();
            if (!$sequence)
            {
                throw new Exception(lang("Query.resultNotFOund"), 1);
            }
            return $this->metroModel->get_station_sequence($stationId)->get()->getResult()[0]->sequence;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得單筆時刻表資料
     * @param string $fromSeq 起站序號
     * @param string $toSeq 訖站序號
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return array 時刻表資料
     * @throws Exception 查無資料
     */
    function get_arrival($fromSeq, $toSeq, $routeId, $direction)
    {
        try
        {
            // 取得總運行時間
            $duration = $this->get_duration($fromSeq, $toSeq, $routeId, $direction);
            
            // 取得時刻表
            return $this->metroModel->get_arrivals($fromSeq, $routeId, $direction, $duration)->get()->getResult();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得單筆運行時間
     * @param string $fromSeq 起站序號
     * @param string $toSeq 訖站序號
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return int 運行時間
     * @throws Exception 查無資料
     */
    function get_duration($fromSeq, $toSeq, $routeId, $direction)
    {
        try
        {
            $duration = $this->metroModel->get_duration($fromSeq, $toSeq, $routeId, $direction)->get()->getResult();
            if (!$duration)
            {
                throw new Exception(lang("Query.resultNotFOund"), 1);
            }
            return $duration[0]->duration;

        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得捷運起訖站序號的行駛方向
     * @param string $fromStationId 起站序號
     * @param string $toStationId 訖站序號
     * @return int 行駛方向（0：去程；1：返程）
     */
    function get_direction($fromSeq, $toSeq)
    {
        try
        {
            if ($fromSeq < $toSeq)
            {
                return 0;
            }
            return 1;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
