<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\ApiBaseControllers\ApiBaseController;
use App\Controllers\ApiBaseControllers\ApiMetroBaseController;
use App\Models\MetroModel;
use Exception;

/**
 * 路線規劃 API 底層控制器
 */
class ApiRoutePlanBaseController extends ApiBaseController
{
    protected $transportNames = [
        "BUS",
        "METRO",
        "THSR",
        "TRA"
    ];

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->metroModel      = new MetroModel();
            $this->metroController = new ApiMetroBaseController();
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 驗證交通工具與車站參數
     * @param string &$transportName 交通工具名稱
     * @param string $stationName 車站參數名稱
     * @param string $stationId 車站參數
     * @return bool 驗證結果
     */
    function validate_transport_param(&$transportName, $stationName, $stationId)
    {
        try
        {
            $this->validateErrMsg = "";

            // 檢查交通工具名稱是否可辨認
            if (!in_array(strtoupper($transportName), $this->transportNames))
            {
                $this->validateErrMsg = lang("RoutePlan.transportNameNotFound", [$transportName]);
                return false;
            }

            // 轉大寫
            $transportName = strtoupper($transportName);

            // 取得車站代碼限制長度
            $stationIdLength = $this->get_station_id_length($transportName);

            // 驗證車站參數
            if (!$this->validate_param($stationName, $stationId, $stationIdLength))
            {
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得車站代碼限制長度
     * @param string $transportName 交通工具名稱
     * @return int 代碼限制長度
     */
    function get_station_id_length($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return parent::BUS_STATION_ID_LENGTH;
                    break;
                case "METRO":
                    return parent::METRO_STATION_ID_LENGTH;
                    break;
                case "THSR":
                    return parent::THSR_STATION_ID_LENGTH;
                    break;
                case "TRA":
                    return parent::TRA_STATION_ID_LENGTH;
                    break;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得路線規劃資料
     * @param string $transportName 運輸工具名稱
     * @param string $startStationId 起站代碼
     * @param string $endStationId 訖站代碼
     * @return array 路線規劃資料
     */
    function get_route_plan($transportName, $startStationId, $endStationId, $departureTime)
    {
        try
        {
            $transferRawData      = $this->get_transfers($transportName);
            $transferData         = $this->get_transfer_adjacency($transferRawData);
            $transferRouteStation = $this->get_transfer_route_station($transferRawData);
            // $stationAdjacency     = $this->get_station_adjacency($transferRouteStation);

            if ($this->is_on_same_route($startStationId, $endStationId))
            {
                return $this->get_arrival($transportName, $startStationId, $endStationId, $departureTime);
            }

            $startRouteId = $this->get_route_by_station($transportName, $startStationId);
            $endRouteId   = $this->get_route_by_station($transportName, $endStationId);
            
            $queue = [
                $startStationId
            ];

            $src = [
                "$startStationId" => -1
            ];

            // 開往訖站的發車時間
            $departureTimes = [
                "$startStationId" => $departureTime
            ];

            $arrivals = [];

            $visitedStation = [];

            foreach ($transferRouteStation[$startRouteId] as $tfStation)
            {
                $arrival = $this->get_arrival($transportName, $startStationId, $tfStation, $departureTime);

                array_push($queue, $tfStation);
                $src[$tfStation] = $startStationId;
                $arrivals[$tfStation] = $arrival;
                $departureTimes[$tfStation] = $arrival["Schedule"]["ArrivalTime"];
            }

            array_shift($queue);
            $visitedStation[$startStationId] = true;

            while ($queue)
            {
                $nowStation   = array_shift($queue);
                $notRouteId = $this->get_route_by_station($transportName, $nowStation);
                $nowTfStation = $transferData[$nowStation]["tfStationId"];

                $visitedStation[$nowStation] = true;

                foreach ($transferRouteStation[$notRouteId] as $tfStation)
                {
                    $arrival = $this->get_arrival($transportName, $nowTfStation, $tfStation, $departureTime);
                    $transferTime = $transferData[$nowTfStation][$tfStation];
                    $tfDptTime = $arrival["Schedule"]["ArrivalTime"] + $transferTime;

                    if ($visitedStation[$tfStation])
                    {
                        continue;
                    }

                    if (!isset($departureTimes[$tfStation]) || $tfDptTime < $departureTimes[$tfStation])
                    {
                        array_push($queue, $tfStation);
                        $src[$tfStation] = $nowTfStation;
                        $arrivals[$tfStation] = $arrival;
                        $departureTimes[$tfStation] = $tfDptTime;
                    }

                }
            }

            foreach ($transferRouteStation[$endRouteId] as $tfStation)
            {
                $arrival = $this->get_arrival($transportName, $tfStation, $endStationId, $departureTime);
                $endDptTime = $arrival["Schedule"]["ArrivalTime"];

                if (!isset($departureTimes[$endStationId]) || $endDptTime < $departureTimes[$endStationId])
                {
                    $src[$endStationId] = $tfStation;
                    $arrivals[$endStationId] = $arrival;
                    $departureTimes[$endStationId] = $endDptTime;
                }
            }
            return $src;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具的所有轉乘資料
     * @param string $transportName 交通工具名稱
     * @return array 轉乘資料
     */
    function get_transfers($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $transfers = $this->metroModel->get_transfers()->get()->getResult();
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            return $transfers;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得路線轉乘資料
     */
    function get_transfer_adjacency(&$transferRawData)
    {
        try
        {
            $graph = [];

            foreach ($transferRawData as $transfer)
            {
                $fromStationId = $transfer->from_station_id;
                $toStationId   = $transfer->to_station_id;
                $transferTime  = $transfer->transfer_time;

                $graph[$fromStationId] = [
                    "tfStationId" => $toStationId,
                    "$toStationId" => $transferTime
                ];
            }

            return $graph;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 檢查兩車站是否處於同一條路線上
     */
    function is_on_same_route($fromStationId, $toStationId)
    {
        try
        {
            $routeId = $this->metroModel->is_on_same_route($fromStationId, $toStationId)->get()->getResult()[0]->route_id;
            return $routeId;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_station_adjacency(&$transferRouteStation)
    {
        try
        {
            $adjacency = [];

            foreach ($transferRouteStation as $routeId => $stations)
            {
                for ($i = 0; $i < sizeof($stations); $i++)
                {
                    for ($j = 0; $j < sizeof($stations); $j++)
                    {
                        if ($i == $j)
                        {
                            continue;
                        }
                        if (!isset($adjacency[$stations[$i]]))
                        {
                            $adjacency[$stations[$i]] = [];
                        }
                        $adjacency[$stations[$i]][$stations[$j]] = true;
                    }
                }
            }
            return $adjacency;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_transfer_route_station(&$transferRawData)
    {
        try
        {
            $graph = [];

            foreach ($transferRawData as $transfer)
            {
                $routeId       = $transfer->from_route_id;
                $fromStationId = $transfer->from_station_id;
                if (!isset($graph[$routeId]))
                {
                    $graph[$routeId] = [];
                }
                array_push($graph[$routeId], $fromStationId);
            }

            return $graph;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具、起訖站及發車時間的最近班次資料
     * @param string $transportName 交通工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $departureTime 發車時間
     * @return array 時刻表資料
     */
    function get_arrival($transportName, $fromStationId, $toStationId, $departureTime)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $transportController = $this->metroController;
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            
            // 取得時刻表資料
            $arrivals = $transportController->get_arrivals($fromStationId, $toStationId);

            // 重新排列資料
            $transportController->restructure_arrivals($arrivals);

            // 取得指定時刻表及發車時間的最近班次
            $arrival = $this->get_arrival_by_time($arrivals, $departureTime);

            //回傳資料
            return $arrival;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定時刻表及發車時間的最近班次
     * @param array &$arrivals 時刻表
     * @param string $time 時間
     * @return array 時刻資訊
     */
    function get_arrival_by_time(&$arrivals, $time)
    {
        try
        {
            helper("getSecondFromTime");

            $timeSec = get_second_from_time($time);

            foreach ($arrivals as $arrival)
            {
                if (get_second_from_time($arrival["Schedule"]["DepartureTime"]) >= $timeSec)
                {
                    return $arrival;
                }
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_route_by_station($transportName, $stationId)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $routeId = $this->metroModel->get_route_by_station($stationId)->get()->getResult()[0]->route_id;
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            return $routeId;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
