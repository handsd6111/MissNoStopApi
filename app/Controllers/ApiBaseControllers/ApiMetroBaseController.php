<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\ApiBaseControllers\ApiBaseController;
use App\Models\MetroModel;
use Exception;


class ApiMetroBaseController extends ApiBaseController
{
    // 載入模型
    protected function __construct()
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
    protected function restructure_systems(&$systems)
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
    protected function restructure_routes(&$routes)
    {
        try
        {
            foreach ($routes as $key => $value)
            {
                $routes[$key] = [
                    "RouteId"   => $value->sub_route_id,
                    "RouteName" => [
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
     * 重新排列捷運時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return void 不回傳值
     */
    protected function restructure_metro_arrivals(&$arrivals)
    {
        try
        {
            foreach ($arrivals as $index => $arrival)
            {
                $this->turn_time_00_to_24($arrival->departure_time);
                $this->turn_time_00_to_24($arrival->arrival_time);

                $arrivals[$index] = [
                    "SubRouteId"   => $arrival->sub_route_id,
                    "Schedule" => [
                        "DepartureTime" => $arrival->departure_time,
                        "ArrivalTime"   => $arrival->arrival_time
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
    protected function turn_time_00_to_24(&$time)
    {
        try
        {
            if (substr($time, 0, 2) == "00")
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
     * @param array 路線資料
     * @throws Exception 查無資料
     */
    protected function get_sub_routes_by_stations($fromStationId, $toStationId, $direction)
    {
        try
        {
            $subRoutes = $this->metroModel->get_sub_routes_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();
            if (!$subRoutes)
            {
                throw new Exception(lang("MetroQueries.stationNotConnected"), 1);
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
     * 取得單筆時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return array 時刻表資料
     * @throws Exception 查無資料
     */
    protected function get_arrival($fromStationId, $toStationId, $subRouteId, $direction)
    {
        try
        {
            // 取得總運行時間
            $duration = $this->get_duration($fromStationId, $toStationId, $subRouteId, $direction);

            // 取得時刻表
            $arrival = $this->metroModel->get_arrivals($fromStationId, $subRouteId, $direction, $duration)->get()->getResult();

            // 回傳資料
            return $arrival;
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
    protected function get_duration($fromStationId, $toStationId, $subRouteId, $direction)
    {
        try
        {
            // 取得起訖站在子路線上的序號
            $fromSeq = $this->get_sub_route_sequence($fromStationId, $subRouteId, $direction);
            $toSeq   = $this->get_sub_route_sequence($toStationId, $subRouteId, $direction);

            // 取得站序大小
            if ($direction == 0)
            {
                $minSeq = $fromSeq;
                $maxSeq = $toSeq;
            }
            else
            {
                $minSeq = $toSeq;
                $maxSeq = $fromSeq;
            }

            // 取得起站的停靠時間
            $stopTime = $this->metroModel->get_stop_time($fromStationId, $subRouteId, $direction)->get()->getResult();
            if (!$stopTime)
            {
                throw new Exception(lang("MetroQueries.stopTimeNotFound"), 1);
            }
            $stopTime = $stopTime[0]->stop_time;
            
            // 取得起訖站間總運行時間
            $duration = $this->metroModel->get_duration($minSeq, $maxSeq, $subRouteId, $direction, $stopTime)->get()->getResult();
            if (!$duration)
            {
                throw new Exception(lang("MetroQueries.durationNotFound"), 1);
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
     * 取得指定捷運起訖站序號的行駛方向
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return int 行駛方向（0：去程；1：返程）
     */
    protected function get_direction($fromStationId, $toStationId)
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
    protected function get_route_sequence($stationId)
    {
        try
        {
            $sequence = $this->metroModel->get_route_sequence($stationId)->get()->getResult();
            if (!$sequence)
            {
                throw new Exception(lang("MetroQueries.stationNotFound"), 1);
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
     * @param string $stationId 捷運站代碼
     * @param int $direction 行駛方向
     * @return int 捷運站序號
     * @throws Exception 查無資料
     */
    protected function get_sub_route_sequence($stationId, $subRouteId, $direction)
    {
        try
        {
            $sequence = $this->metroModel->get_sub_route_sequence($stationId, $subRouteId, $direction)->get()->getResult();
            if (!$sequence)
            {
                throw new Exception(lang("MetroQueries.stationNotFound"), 1);
            }
            return $sequence[0]->sequence;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
