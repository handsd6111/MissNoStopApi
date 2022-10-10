<?php

namespace App\Controllers;

use App\Models\MetroModel;
use App\Models\ORM\MetroArrivalModel;
use App\Models\ORM\MetroDurationModel;
use App\Models\ORM\MetroLineModel;
use App\Models\ORM\MetroLineStationModel;
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
            // query builder
            $this->metroModel = new MetroModel();

            // ORM
            $this->MAModel  = new MetroArrivalModel();
            $this->MDModel  = new MetroDurationModel();
            $this->MLModel  = new MetroLineModel();
            $this->MLSModel = new MetroLineStationModel();
            $this->MRModel  = new MetroRouteModel();
            $this->MRSModel = new MetroRouteStationModel();
            $this->MSModel  = new MetroStationModel();
            $this->MSTModel = new MetroSystemModel();
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

    /**
     * 從 TDX 取得捷運路線代碼
     */
    public function getMetroLines($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Line/$railSystem?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 從 TDX 寫入捷運路線代碼至 DB
     */
    public function setMetroLines($railSystem)
    {
        try
        {
            // 取得路線資料
            $lines = $this->getMetroLines($railSystem);

            foreach ($lines as $line)
            {
                // 開始計時
                $startTime = $this->getTime();
                
                // 取得路線代碼
                $lineId = $this->getUID($railSystem, $line->LineID);

                $this->terminalLog("Running data of $lineId ... ");

                if (!isset($line->LineName->En))
                {
                    $line->LineName->En = $line->LineName->Zh_tw;
                }

                $this->MLModel->save([
                    "ML_id"        => $lineId,
                    "ML_name_TC"   => $line->LineName->Zh_tw,
                    "ML_name_EN"   => $line->LineName->En,
                    "ML_system_id" => $railSystem
                ]);

                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運路線。
     */
    public function setMetroLineAll()
    {
        try
        {
            $systems = $this->getMetroSystem();
            foreach ($systems as $system)
            {
                $this->setMetroLines($system->MST_id);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true);
            log_message("critical", $e);
        }
    }

    // ============== Metro Route ==============

    /**
     * 從 TDX 取得捷運子路線資料。
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
     * 利用 ORM Model 寫入單個捷運系統的子路線至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroRoute($railSystem)
    {
        try
        {
            // 取得子路線資料
            $routes = $this->getMetroRoute($railSystem);

            // 若查無結果則停止
            if (empty($routes))
            {
                return false;
            }

            // 走遍子路線資料
            foreach ($routes as $route)
            {
                // 開始計時
                $startTime = $this->getTime();
                $this->terminalLog("Running data of $railSystem-{$route->RouteID} ... ");

                // 若子路線無中文名稱則以空白代替
                if (!isset($route->RouteName->Zh_tw))
                {
                    $route->RouteName->Zh_tw = "";
                }

                // 若子路線無英文名稱則以中文代替
                if (!isset($route->RouteName->Zh_tw))
                {
                    $route->RouteName->En = $route->RouteName->Zh_tw;
                }

                $lineId = $this->getUID($route->OperatorCode, $route->LineID);

                // 寫入子路線資料
                $this->MRModel->save([
                    'MR_id'      => $this->getUID($railSystem, $route->RouteID),
                    'MR_name_TC' => $route->RouteName->Zh_tw,
                    'MR_name_EN' => $route->RouteName->En,
                    'MR_line_id' => $lineId
                ]);

                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);
            }

            return true;
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運子路線。
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
            $this->terminalLog($e, true);
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
            // 開始計時
            $startTime = $this->getTime();
            $this->terminalLog("Running data of $railSystem stations ... ");

            // 取得車站資料
            $stations = $this->getMetroStation($railSystem);

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
                    'MS_name_TC'   => $station->StationName->Zh_tw,
                    'MS_name_EN'   => $station->StationName->En,
                    'MS_city_id'   => $station->LocationCityCode,
                    'MS_longitude' => $station->StationPosition->PositionLon,
                    'MS_latitude'  => $station->StationPosition->PositionLat
                ]);
            }

            // 印出花費時間
            $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);

            return true;
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
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
            $this->terminalLog($e, true);
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
     * 取得指定路線及起訖站的行駛方向
     * @param string $fromStationId
     * @param string $toStationId
     * @return int 行駛方向（0：去程；1：返程）
     */
    public function getStationsDirection($fromStationId, $toStationId)
    {
        try
        {
            $fromSeq = $this->metroModel->get_station_sequence($fromStationId)->get()->getResult()[0]->sequence;
            $toSeq   = $this->metroModel->get_station_sequence($toStationId)->get()->getResult()[0]->sequence;

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

            // 取得相反的行駛方向
            $reverseDirection = [1, 0];

            // 走遍每條路線
            foreach ($routes as $route)
            {
                // 開始計時
                $startTime = $this->getTime();

                // 若查無運行時間資料則跳過
                if (!isset($route->TravelTimes))
                {
                    continue;
                }

                $this->terminalLog("Running data of $railSystem-{$route->RouteID} ... ");

                // 取得子路線代碼、起訖站代碼及行駛方向
                $routeId = $this->getUID($railSystem, $route->RouteID);

                //走遍運行時間資料
                foreach ($route->TravelTimes as $travelTime)
                {
                    // 取得行駛方向
                    $direction = $this->getStationsDirection($travelTime->FromStationID, $travelTime->ToStationID);

                    // 取得捷運站代碼
                    $stationId = $this->getUID($railSystem, $travelTime->FromStationID);

                    // 寫入資料
                    $this->MDModel->save([
                        "MD_station_id" => $stationId,
                        "MD_route_id"   => $routeId,
                        "MD_direction"  => $direction,
                        "MD_duration"   => $travelTime->RunTime,
                        "MD_stop_time"  => $travelTime->StopTime
                    ]);

                    // 寫入資料（相反方向）
                    $this->MDModel->save([
                        "MD_station_id" => $stationId,
                        "MD_route_id"   => $routeId,
                        "MD_direction"  => $reverseDirection[$direction],
                        "MD_duration"   => $travelTime->RunTime,
                        "MD_stop_time"  => $travelTime->StopTime
                    ]);
                }

                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
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
    public function getMetroLineStation($railSystem)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationOfLine/$railSystem?%24format=JSON";
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
    public function setMetroLineStation($railSystem)
    {
        try
        {
            // 取得路線的車站資料
            $lines = $this->getMetroLineStation($railSystem);

            // 走遍路線資料
            foreach ($lines as $line)
            {
                // 開始計時
                $startTime = $this->getTime();

                // 取得路線代碼
                $lineId = $this->getUID($railSystem, $line->LineID);

                $this->terminalLog("Running data of $lineId ... ");

                // 走遍路線的車站資料
                foreach ($line->Stations as $station)
                {
                    $this->MLSModel->save([
                        "MLS_station_id" => $this->getUID($railSystem, $station->StationID),
                        "MLS_line_id"    => $lineId,
                        "MLS_sequence"   => $station->Sequence
                    ]);
                }

                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);
            }

            return true;
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 一次性設定所有捷運車站與路線之間關聯的資料。
     */
    public function setMetroLineStationAll()
    {
        try
        {
            $systems = $this->getMetroSystem();
            foreach ($systems as $system)
            {
                $this->setMetroLineStation($system->MST_id);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 取得捷運子路線車站資料
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
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 寫入指定捷運系統的子路線車站資料
     */
    public function setMetroRouteStation($railSystem)
    {
        try
        {
            $routeStations = $this->getMetroRouteStation($railSystem);
            foreach ($routeStations as $routeStation)
            {
                $routeId   = $this->getUID($railSystem, $routeStation->RouteID);
                $direction = $routeStation->Direction;
                foreach ($routeStation->Stations as $Station)
                {
                    $stationId = $this->getUID($railSystem, $Station->StationID);
                    $this->MRSModel->save([
                        "MRS_station_id" => $stationId,
                        "MRS_route_id"   => $routeId,
                        "MRS_direction"  => $direction,
                        "MRS_sequence"   => $Station->Sequence
                    ]);
                }
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 寫入所有捷運系統的子路線車站資料
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
            $this->terminalLog($e, true, true);
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
                // 開始計時
                $startTime = $this->getTime();

                // 取得路線代碼、起站代碼及行駛方向
                $routeId   = $this->getUID($railSystem, $arrival->RouteID);
                $stationId = $this->getUID($railSystem, $arrival->StationID);
                $direction = $arrival->Direction;

                 // 若今日無發車則跳過
                if (!$arrival->ServiceDay->$weekDay)
                {
                    continue;
                }

                $this->terminalLog("Running data of $railSystem-{$arrival->StationID} ... ");

                // 走遍時刻表
                foreach ($arrival->Timetables as $timeTable)
                {
                    // 寫入時刻表資料
                    $this->MAModel->save([
                        'MA_station_id'   => $stationId,
                        'MA_route_id'     => $routeId,
                        'MA_direction'    => $direction,
                        'MA_sequence'     => $timeTable->Sequence,
                        'MA_arrival_time' => $timeTable->ArrivalTime
                    ]);
                }
                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . " seconds taken.", true);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }
}
