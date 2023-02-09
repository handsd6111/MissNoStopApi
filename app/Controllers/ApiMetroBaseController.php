<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use App\Models\MetroModel;
use Exception;
use stdClass;

class ApiMetroBaseController extends ApiBaseController
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
     * 重新排列系統資料陣列
     * @param mixed &$systems 系統資料陣列
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
     * 重新排列路線資料陣列
     * @param mixed &$routes 路線資料陣列
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
     * 重新排列車站資料陣列
     * @param mixed &$stations 路線車站陣列
     */
    function restructure_stations(&$stations)
    {
        try
        {
            for ($i = 0; $i < sizeof($stations); $i++)
            {
                $station = $stations[$i];

                $stations[$i] = [
                    "StationId"   => $station->station_id,
                    "StationName" => [
                        "TC" => $station->station_name_TC,
                        "EN" => $station->station_name_EN
                    ],
                    "StationLocation" => [
                        "CityId"   => $station->city_id,
                        "Longitude" => $station->longitude,
                        "Latitude"  => $station->latitude,
                    ],
                    "Sequence" => $station->sequence
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列最近車站資料陣列
     * @param mixed &$stations 路線最近車站陣列
     */
    function restructure_nearest_stations(&$stations)
    {
        try
        {
            for ($i = 0; $i < sizeof($stations); $i++)
            {
                $station = $stations[$i];

                $stations[$i] = [
                    "SystemId" => $station->system_id,
                    "SystemName" => [
                        "TC" => $station->system_name_TC,
                        "EN" => $station->system_name_EN,
                    ],
                    "RouteId" => $station->route_id,
                    "RouteName" => [
                        "TC" => $station->route_name_TC,
                        "EN" => $station->route_name_EN,
                    ],
                    "StationId"   => $station->station_id,
                    "StationName" => [
                        "TC" => $station->station_name_TC,
                        "EN" => $station->station_name_EN
                    ],
                    "StationLocation" => [
                        "CityId"   => $station->city_id,
                        "Longitude" => $station->longitude,
                        "Latitude"  => $station->latitude,
                    ],
                    "Sequence" => $station->sequence
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列時刻表資料陣列
     * @param array &$arrivals 時刻表資料陣列
     */
    function restructure_arrivals(&$arrivals)
    {
        try
        {
            foreach ($arrivals as $i => $arrival)
            {
                $arrivals[$i] = [
                    "Schedule" => [
                        "DepartureTime" => $arrival->departure_time,
                        "ArrivalTime" => $arrival->arrival_time
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
     * 重新排列路線時刻表資料陣列
     * @param array &$arrivals 路線時刻表資料陣列
     */
    function restructure_arrivals_by_route(&$arrivals)
    {
        try
        {
            foreach($arrivals as $i => $arrival)
            {
                $arrivals[$i] = [
                    "StationId" => $arrival->station_id,
                    "StationName" => [
                        "TC" => $arrival->station_name_TC,
                        "EN" => $arrival->station_name_EN,
                    ],
                    "Schedule" => [
                        "ArrivalTime" => $arrival->arrival_time,
                        "DepartureTime" => $arrival->arrival_time
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
     * 取得指定條件的時刻表資料
     * @param string $fromStationId 起站代碼
     * @param int $direction 行駛方向
     * @param string $subRoutes 子路線
     * @param int $durations 運行時間
     * @param string $arrivalTime 目前時間
     * @return object 時刻表資料
     */
    function get_arrivals($fromStationId, $direction, $subRoutes, $durations, $arrivalTime = null)
    {
        try
        {
            helper(["addTime", "time00To24"]);
            // 取得起站時刻表資料
            $arrivals = $this->metroModel->get_arrivals_minimum($fromStationId, $direction, $subRoutes, $arrivalTime);

            if ($arrivalTime != null)
            {
                $arrivals = $arrivals->get(1)->getResult();
            }
            else
            {
                $arrivals = $arrivals->get()->getResult();
            }
            foreach ($arrivals as $i => $arrival)
            {
                $subRouteId   = $arrival->sub_route_id;
                $depatureTime = time_00_to_24($arrival->departure_time);
                $duration     = $durations[$subRouteId];

                $arrivals[$i]->arrival_time = new stdClass();
                $arrivals[$i]->arrival_time = add_time($depatureTime, $duration);

                $arrivals[$i]->duration = new stdClass();
                $arrivals[$i]->duration = $duration;
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
     * 取得單筆時刻表的運行時間
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param array $subRoutes 子路線陣列
     * @param int $direction 行駛方向
     * @return array 運行時間
     */
    function get_durations($fromStationId, $toStationId, $subRoutes, $direction)
    {
        try
        {
            foreach ($subRoutes as $i => $subRouteId)
            {
                // 取得起訖站在子路線上的序號
                $fromSeq = $this->get_sub_route_sequence($fromStationId, $subRouteId, $direction);
                $toSeq   = $this->get_sub_route_sequence($toStationId, $subRouteId, $direction);
    
                // 取得起站停靠時間
                $stopTime = $this->get_stop_time($fromStationId, $subRouteId, $direction);
    
                $smallerSeq = $fromSeq;
                $largerSeq  = $toSeq;
    
                if ($fromSeq > $toSeq)
                {
                    $smallerSeq = $toSeq;
                    $largerSeq  = $fromSeq;
                }
                // 取得起訖站間總運行時間
                $duration = $this->metroModel->get_duration($smallerSeq, $largerSeq, $subRouteId, $direction, $stopTime)->get()->getResult();
                
                if (!isset($duration[0]->duration))
                {
                    throw new Exception(lang("MetroQueries.durationNotFound", [$fromStationId, $toStationId]), 400);
                }
                $durations[$subRouteId] = $duration[0]->duration;
            }

            // 回傳資料
            return $durations;

        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運站、子路線及行駛方向的停靠時間
     * @param string $fromStationId 起站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return int 停靠時間（秒數）
     */
    function get_stop_time($fromStationId, $subRouteId, $direction)
    {
        try
        {
            $stopTime = $this->metroModel->get_stop_time($fromStationId, $subRouteId, $direction)->get()->getResult();
            // 若查無停靠時間則回傳錯誤訊息
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
     * 取得指定捷運起訖站的行駛方向
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
     * 取得起訖站皆行經的路線資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param int $direction 行駛方向
     * @param array 路線資料（已剩純資料）
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
     * 取得指定捷運站在子路線上的序號
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return int 捷運站序號
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
