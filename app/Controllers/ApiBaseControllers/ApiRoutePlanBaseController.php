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
    protected function validate_transport_param(&$transportName, $stationName, $stationId)
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
    protected function get_station_id_length($transportName)
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
     * 取得指定交通工具及捷運站的路線代碼
     * @param string $transportName 交通工具名稱
     * @param string $stationId 捷運站代碼
     * @return array 轉乘資料
     */
    protected function get_route_by_station($transportName, $stationId)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $route = $this->metroModel->get_route_by_station($stationId)->get()->getResult();
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }

            if (!$route)
            {
                throw new Exception(lang("Query.routeNotFound", [$stationId]), 1);
            }

            return $route[0]->route_id;
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
    protected function get_transfers($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    return $this->get_metro_transfers();
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得捷運轉乘資料
     * @return array 轉乘資料
     */
    protected function get_metro_transfers()
    {
        try
        {
            $transfers = $this->metroModel->get_transfers()->get()->getResult();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具及起訖站的時刻表資料
     * @param string $transportName 交通工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 時刻表資料
     */
    protected function get_arrival($transportName, $fromStationId, $toStationId, $startTime)
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

            // 取得指定時刻表及時間的最快時刻
            $arrival = $this->get_arrival_by_time($arrivals, $startTime);

            //回傳資料
            return $arrival;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定時刻表及時間的最快時刻
     * @param array &$arrivals 時刻表
     * @param string $time 時間
     * @return array 時刻資訊
     */
    protected function get_arrival_by_time(&$arrivals, $time)
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
}
