<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\ApiBaseControllers\ApiBaseController;
use App\Models\MetroModel;
use Exception;

class ApiMetroBaseController extends ApiBaseController
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
     * 重新排列捷運系統資料
     * @param array &$systems 系統資料
     * @return void 不回傳值
     */
    function restructure_systems(&$systems)
    {
        try
        {
            foreach ($systems as $key => $value)
            {
                $systems[$key] = [
                    "SystemId"   => $value->system_id,
                    "SystemName" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ],
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列捷運路線資料
     * @param array &$routes 路線資料
     * @return void 不回傳值
     */
    function restructure_routes(&$routes)
    {
        try
        {
            foreach ($routes as $key => $value)
            {
                $routes[$key] = [
                    "RouteId"   => $value->route_id,
                    "RouteName" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ]
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列捷運時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return void 不回傳值
     */
    function restructure_arrivals(&$arrivals, $fromStationId, $toStationId)
    {
        try
        {
            foreach ($arrivals as $index => $arrival)
            {
                $this->turn_time_00_to_24($arrival->departure_time);
                $this->turn_time_00_to_24($arrival->arrival_time);

                $arrivals[$index] = [
                    "RouteId"       => $arrival->route_id,
                    "SubRouteId"    => $arrival->sub_route_id,
                    "FromStationId" => $fromStationId,
                    "ToStationId"   => $toStationId,
                    "Schedule" => [
                        "DepartureTime" => $arrival->departure_time,
                        "ArrivalTime"   => $arrival->arrival_time,
                        "Duration"      => $arrival->duration
                    ]
                ];
            }
            usort($arrivals, [ApiBaseController::class, "cmpArrivals"]);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 檢查並將時刻為「0」點的時間改為「24」點
     * @param string &$time 時間
     * @return void 不回傳值
     */
    function turn_time_00_to_24(&$time)
    {
        try
        {
            if ($time[0] == '0' && $time[1] == '0')
            {
                $time[0] = '2';
                $time[1] = '4';
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
     * @param int $direction 行駛方向
     * @param array 路線資料（已剩純資料）
     * @throws Exception 查無資料
     */
    function get_sub_routes_by_stations($fromStationId, $toStationId, $direction)
    {
        try
        {
            $subRoutes = $this->metroModel->get_sub_routes_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();
            if (!$subRoutes)
            {
                throw new Exception(lang("MetroQueries.stationNotConnected", [$fromStationId, $toStationId]), 400);
            }
            for ($i = 0; $i < sizeof($subRoutes); $i++)
            {
                $subRoutes[$i] = $subRoutes[$i]->sub_route_id;
            }
            return $subRoutes;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起訖站的時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return array 時刻表資料
     * @throws Exception 查無資料
     */
    function get_arrivals_new($fromStationId, $toStationId)
    {
        try
        {
            helper("addTime");

            // 取得行駛方向
            $direction = $this->get_direction($fromStationId, $toStationId);

            // 取得起訖站皆行經的所有捷運子路線
            $subRoutes = $this->get_sub_routes_by_stations($fromStationId, $toStationId, $direction);

            // 取得起站時刻表資料
            $arrivals = $this->metroModel->get_arrivals_new($fromStationId, $direction, $subRoutes)->get()->getResult();

            $durations = [];

            foreach ($subRoutes as $i => $subRouteId)
            {
                // 取得指定子路線的總運行時間
                $durations[$subRouteId] = $this->get_duration($fromStationId, $toStationId, $subRouteId, $direction);
            }

            foreach ($arrivals as $i => $arrival)
            {
                $this->turn_time_00_to_24($arrival->departure_time);

                $routeId      = $arrival->route_id;
                $subRouteId   = $arrival->sub_route_id;
                $duration     = $durations[$subRouteId];
                $depatureTime = $arrival->departure_time;
                $arrivalTime  = add_time($depatureTime, $duration);

                $arrivals[$i] = [
                    "RouteId"       => $routeId,
                    "SubRouteId"    => $subRouteId,
                    "FromStationId" => $fromStationId,
                    "ToStationId"   => $toStationId,
                    "Schedule" => [
                        "DepartureTime" => $depatureTime,
                        "ArrivalTime"   => $arrivalTime,
                        "Duration"      => $duration
                    ]
                ];
            }


            // 回傳時刻表資料
            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得單筆時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return array 時刻表資料
     * @throws Exception 查無資料
     */
    function get_arrival($fromStationId, $toStationId, $subRouteId, $direction)
    {
        try
        {
            // 取得總運行時間
            $duration = $this->get_duration($fromStationId, $toStationId, $subRouteId, $direction);

            // 取得時刻表
            $arrivals = $this->metroModel->get_arrivals($fromStationId, $subRouteId, $direction, $duration)->get()->getResult();

            // 回傳資料
            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得單筆運行時間
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return int 運行時間
     * @throws Exception 查無資料
     */
    function get_duration($fromStationId, $toStationId, $subRouteId, $direction)
    {
        try
        {
            // 取得起訖站在子路線上的序號
            $fromSeq = $this->get_sub_route_sequence($fromStationId, $subRouteId, $direction);
            $toSeq   = $this->get_sub_route_sequence($toStationId, $subRouteId, $direction);

            // 取得起站停靠時間
            $stopTime = $this->get_stop_time($fromStationId, $subRouteId, $direction);
            
            // 取得起訖站間總運行時間
            $duration = $this->metroModel->get_duration($fromSeq, $toSeq, $subRouteId, $direction, $stopTime)->get()->getResult();
            if (!isset($duration[0]->duration))
            {
                throw new Exception(lang("MetroQueries.durationNotFound", [$fromStationId, $toStationId]), 400);
            }
            $duration = $duration[0]->duration;

            // 回傳資料
            return $duration;

        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運站、子路線及行駛方向的停靠時間
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return int 停靠時間（秒數）
     */
    function get_stop_time($fromStationId, $subRouteId, $direction)
    {
        try
        {
            $stopTime = $this->metroModel->get_stop_time($fromStationId, $subRouteId, $direction)->get()->getResult();
            if (!isset($stopTime[0]->stop_time))
            {
                throw new Exception(lang("MetroQueries.stopTimeNotFound"), 400);
            }
            return $stopTime[0]->stop_time;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運起訖站序號的行駛方向
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return int 行駛方向（0：去程；1：返程）
     */
    function get_direction($fromStationId, $toStationId)
    {
        try
        {
            // 取得起訖站序號
            $fromSeq = $this->get_route_sequence($fromStationId);
            $toSeq   = $this->get_route_sequence($toStationId);

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

    /**
     * 取得指定捷運站在路線上的序號
     * @param string $stationId 捷運站代碼
     * @return int 捷運站序號
     * @throws Exception 查無資料
     */
    function get_route_sequence($stationId)
    {
        try
        {
            $sequence = $this->metroModel->get_route_sequence($stationId)->get()->getResult();
            if (!isset($sequence[0]->sequence))
            {
                throw new Exception(lang("MetroQueries.stationNotFound", [$stationId]), 400);
            }
            return $sequence[0]->sequence;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運站在子路線上的序號
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return int 捷運站序號
     * @throws Exception 查無資料
     */
    function get_sub_route_sequence($stationId, $subRouteId, $direction)
    {
        try
        {
            $sequence = $this->metroModel->get_sub_route_sequence($stationId, $subRouteId, $direction)->get()->getResult();
            if (!isset($sequence[0]->sequence))
            {
                throw new Exception(lang("MetroQueries.stationNotFound"), 400);
            }
            return $sequence[0]->sequence;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
