<?php

namespace App\Controllers;


use App\Models\ORM\MetroArrivalModel;
use App\Models\ORM\MetroDurationModel;
use App\Models\ORM\MetroRouteModel;
use App\Models\ORM\MetroRouteStationModel;
use App\Models\ORM\MetroStationModel;
use App\Models\ORM\MetroSystemModel;
use Exception;

class TdxMetroController extends TdxBaseController
{
    function __construct()
    {
        try
        {
            
            $this->MSTModel = new MetroSystemModel();
            $this->MRModel  = new MetroRouteModel();
            $this->MRSModel = new MetroRouteStationModel();
            $this->MSModel  = new MetroStationModel();
            $this->MDModel  = new MetroDurationModel();
            $this->MAModel  = new MetroArrivalModel();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    // ============== Metro System ==============


    /**
     * 利用 ORM Model 取得捷運系統列表。
     * 
     * @return object[](stdClass)
        {
            MST_id => string,
            MST_name_TC => string,
            MST_name_EN => string,
        }
     */
    public function getMetroSystem()
    {
        try
        {
            $systems = $this->MSTModel->get()->getResult();
            return $systems;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    // ============== Metro Route ==============

    /**
     * 從 TDX 取得捷運路線資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass)
     * {
     *     LineNo => string
     *     LineID => string
     *     LineName => object(stdClass) {
     *         Zh_tw => string | null
     *         En => string | null
     *     }
     *     LineSectionName => object(stdClass) {}
     *     IsBranch => bool(false)
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroRoute($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Route/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的路線至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroRoute($railSystem)
    {
        try
        {
            // 取得路線資料
            $routes = $this->getMetroRoute($railSystem);

            // 若查無結果則停止
            if (empty($routes))
            {
                return false;
            }

            // 走遍路線資料
            foreach ($routes as $route)
            {
                // 若無中文路線名稱則以空白代替
                if (!isset($route->LineName->Zh_tw))
                {
                    $route->LineName->Zh_tw = "";
                }

                // 若無英文路線名稱則以中文代替
                if (!isset($route->LineName->Zh_tw))
                {
                    $route->LineName->En = $route->LineName->Zh_tw;
                }

                // 整理寫入資料
                $saveData = [
                    'MR_id' => $this->getUID($railSystem, $route->LineNo),
                    'MR_name_TC' => $route->LineName->Zh_tw,
                    'MR_name_EN' => $route->LineName->En,
                    'MR_system_id' => $railSystem
                ];

                // 寫入路線資料
                $this->MRModel->save($saveData); //orm save data
            }

            return true;
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運路線。
     */
    public function setMetroRouteAll()
    {
        try
        {
            $systems = $this->getMetroSystem();
            foreach ($systems as $system)
            {
                $this->setMetroRoute($system->MST_id);
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    // ============== Metro Station ==============

    /**
     * 從 TDX 取得捷運車站資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass)
     * {
     *     LineNo => string
     *     StationUID => string 
     *     StationID => string
     *     StationName => object(stdClass) {
     *         Zh_tw => string 
     *         En => string(8) 
     *     }
     *     StationAddress => string 
     *     BikeAllowOnHoliday => bool
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     *     StationPosition => object(stdClass) {
     *         PositionLon => float
     *         PositionLat => float
     *         GeoHash => string
     *     }
     *     LocationCity => string
     *     LocationCityCode => string
     *     LocationTown => string
     *     LocationTownCode => string
     * }
     */
    public function getMetroStation($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Station/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的車站至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroStation($railSystem)
    {
        try
        {
            // 取得車站資料
            $stations  = $this->getMetroStation($railSystem);
        
            // 走遍車站資料
            foreach ($stations as $station)
            {
                // 若無中文站名則以空白代替
                if (!isset($station->StationName->Zh_tw))
                {
                    $station->StationName->Zh_tw = "";
                }

                //若無英文站名則以中文站明代替
                if (!isset($station->StationName->En))
                {
                    $station->StationName->En = $station->StationName->Zh_tw;
                }

                // 寫入車站資料
                $this->MSModel->save([
                    'MS_id'        => $station->StationUID,
                    'MS_name_TC'   => isset($station->StationName->Zh_tw) ? $station->StationName->Zh_tw : "",
                    'MS_name_EN'   => isset($station->StationName->En) ? $station->StationName->En : "",
                    'MS_city_id'   => $station->LocationCityCode,
                    'MS_longitude' => $station->StationPosition->PositionLon,
                    'MS_latitude'  => $station->StationPosition->PositionLat
                ]);
            }

            return true;
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運車站。
     */
    public function setMetroStationAll()
    {
        try
        {
            $systems = $this->getMetroSystem();
            foreach ($systems as $system)
            {
                $this->setMetroStation($system->MST_id);
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    // ============== Metro Duration ==============

    /**
     * 從 TDX 取得捷運車站資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass) 
     * {
     *     LineNo => string
     *     LineID => string
     *     RouteID => string
     *     TrainType => int
     *     TravelTimes => object[](stdClass) {
     *         Sequence => int
     *         FromStationID => string
     *         FromStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *         }
     *         ToStationID => string
     *         ToStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *         }
     *         RunTime => int
     *         StopTime => int
     *     }
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroDuration($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/S2STravelTime/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的運行時間至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroDuration($railSystem)
    {
        try
        {
            // 取得路線車站資料
            $routes = $this->getMetroDuration($railSystem);

            // 走遍每條路線
            foreach ($routes as $route)
            {
                // 若查無運行時間資料則跳過
                if (!isset($route->TravelTimes))
                {
                    continue;
                }

                $fromStationId = $route->TravelTimes[0]->FromStationID;
                $toStationId   = $route->TravelTimes[0]->ToStationID;
                $direction     = 1;

                // 取得行駛方向：若起站代碼長度不大於訖站代碼長度，且起站代碼小於訖站代碼，則行駛方向為去程（0）
                if (sizeof($fromStationId) <= sizeof($toStationId) && !strcmp($fromStationId, $toStationId))
                {
                    $direction = 0;
                }

                //走遍運行時間資料
                foreach ($route->TravelTimes as $travelTime)
                {
                    // 寫入資料
                    $this->MDModel->save([
                        "MD_station_id" => $travelTime->FromStationID,
                        "MD_direction"  => $direction,
                        "MD_duration"   => $travelTime->RunTime,
                        "MD_stop_time"  => $travelTime->StopTime
                    ]);
                }
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    // ============== Metro Route Station ==============

    /**
     * 從 TDX 取得捷運車站與路線之間關聯的資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[] 資料格式，我忘了那個怎麼把他叫出來所以移除了回傳資料格式
     */
    public function getMetroRouteStation($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationOfRoute/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統車站與路線之間關聯的資料至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroRouteStation($railSystem)
    {
        try
        {
            // 取得路線的車站資料
            $routes = $this->getMetroRouteStation($railSystem);

            // 走遍路線資料
            foreach ($routes as $route)
            {
                // 只使用行駛方向為 0（去程）的資料
                if ($route->Direction != 0)
                {
                    continue;
                }

                // 走遍路線的車站資料
                foreach ($route->Stations as $station)
                {
                    $this->MRSModel->save([
                        "MRS_station_id" => $station->StationID,
                        "MRS_route_id"   => $route->RouteID,
                        "MRS_sequence"   => $station->Sequence
                    ]);
                }
            }

            return true;
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運車站與路線之間關聯的資料。
     */
    public function setMetroRouteStationAll()
    {
        try
        {
            $systems = $this->getMetroSystem();
            foreach ($systems as $system)
            {
                $this->setMetroRouteStation($system->MST_id);
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    // ============== Metro Arrival ==============

    /**
     * 從 TDX 取得兩個捷運車站之間的時刻表資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return object[](stdClass) 
     * {
     *     RouteID => string
     *     LineID => string
     *     StationID => string
     *     StationName => object(stdClass) {
     *          Zh_tw => string
     *          En => string
     *     }
     *     Direction => int
     *     DestinationStaionID => string
     *     DestinationStationName => object(stdClass) {
     *          Zh_tw => string
     *          En => string
     *     }
     *     Timetables => object[](stdClass) {
     *          Sequence => int
     *          ArrivalTime => string
     *          DepartureTime => string
     *          TrainType => int
     *     }
     *     ServiceDay => object(stdClass) {
     *          ServiceTag => string
     *          Monday => bool
     *          Tuesday => bool
     *          Wednesday => bool
     *          Thursday => bool
     *          Friday => bool
     *          Saturday => bool
     *          Sunday => bool
     *          NationalHolidays => bool
     *     }
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroArrival($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationTimeTable/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入兩個捷運車站之間的時刻表資料至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroArrival($railSystem)
    {
        try
        {
            helper("getWeekDay");

            // 取得今天星期幾
            $weekDay = get_week_day(true);

            // 取得時刻表資料
            $arrivals = $this->getMetroArrival($railSystem);

            // 走遍時刻表資料
            foreach ($arrivals as $arrival)
            {
                // 取得起站
                $stationId = $this->getUID($railSystem, $arrival->StationID);
                // 取得行駛方向
                $direction = $arrival->Direction;

                 // 若今日無發車則跳過
                if (!$arrival->ServiceDay->$weekDay)
                {
                    continue;
                }

                // 走遍時刻表
                foreach ($arrival->Timetables as $timeTable)
                {
                    // 寫入時刻表資料
                    $this->MAModel->save([
                        'MA_station_id'   => $stationId,
                        'MA_direction'    => $direction,
                        'MA_sequence'     => $timeTable->Sequence,
                        'MA_arrival_time' => $timeTable->ArrivalTime
                    ]);
                }
            }

            return true;
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }
}
