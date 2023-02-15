<?php

namespace App\Controllers;

use App\Models\BusModel;
use App\Models\ORM\BusArrivalModel;
use App\Models\ORM\BusRouteStationModel;
use App\Models\ORM\BusRouteModel;
use App\Models\ORM\BusStationModel;
use App\Models\ORM\CityModel;
use Exception;
use stdClass;

class TdxBusController extends TdxBaseController
{
    protected $cityModel;
    protected $busArrivalModel;
    protected $busModel;
    protected $busRouteStationModel;
    protected $busRouteModel;
    protected $busStationModel;

    /**
     * 載入模型
     */
    public function __construct()
    {
        try
        {
            $this->cityModel            = new CityModel();
            $this->busArrivalModel      = new BusArrivalModel();
            $this->busModel             = new BusModel();
            $this->busRouteStationModel = new BusRouteStationModel();
            $this->busRouteModel        = new BusRouteModel();
            $this->busStationModel      = new BusStationModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }
    /**
     * 取得縣市列表
     * @return array 縣市列表
     */
    public function getCities()
    {
        try
        {
            $result = $this->cityModel->findAll();

            return $result;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定縣市的公車路線與車站資料
     * @param string $cityNameEN 縣市英文名稱
     * @return mixed 公車路線與車站資料
     * @see https://tdx.transportdata.tw/api-service/swagger/basic/#/CityBus/CityBusApi_StopOfRoute_2039
     */
    public function getBusRouteStation($cityNameEN)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Bus/StopOfRoute/City/$cityNameEN?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 寫入公車路線與車站資料
     */
    public function setBusRouteStation()
    {
        try
        {
            $cities = $this->getCities();

            foreach ($cities as $city)
            {
                $cityId    = $city["C_id"];
                $cityName  = $city["C_name_EN"];

                $startTime = $this->getTime();
                $this->terminalLog("Downloading data of $cityName ... ", true);

                // 取得指定縣市的公車路線與車站資料
                $routes = $this->getBusRouteStation($cityName);

                // 走遍指定縣市的路線列表
                foreach ($routes as $route)
                {
                    // 若無英文路名則以中文路名代替
                    if (!isset($route->SubRouteName->En))
                    {
                        $route->SubRouteName->En = $route->SubRouteName->Zh_tw;
                    }
                    $routeId     = $this->getUID($cityId, $route->SubRouteID);
                    $routeNameTc = $route->SubRouteName->Zh_tw;
                    $routeNameEn = $route->SubRouteName->En;
                    $direction   = $route->Direction;

                    $routeStartTime = $this->getTime();
                    $this->terminalLog("Downloading route: $routeNameEn ... ");

                    $this->busRouteModel->save([
                        "BR_id"        => $routeId,
                        "BR_name_TC"   => $routeNameTc,
                        "BR_name_EN"   => $routeNameEn
                    ]);
                    // 走遍指定路線的車站列表
                    foreach ($route->Stops as $station)
                    {
                        // 若無車站代碼則直接跳過
                        if (!isset($station->StopID))
                        {
                            continue;
                        }
                        // 若無英文站名則以中文站名代替
                        if (!isset($station->StopName->En))
                        {
                            $station->StopName->En = $station->StopName->Zh_tw;
                        }
                        $stationId = $this->getUID($cityId, $station->StopID);

                        $this->busStationModel->save([
                            "BS_id"        => $stationId,
                            "BS_name_TC"   => $station->StopName->Zh_tw,
                            "BS_name_EN"   => $station->StopName->En,
                            "BS_city_id"   => $cityId,
                            "BS_longitude" => $station->StopPosition->PositionLon,
                            "BS_latitude"  => $station->StopPosition->PositionLat
                        ]);
                        $this->busRouteStationModel->save([
                            "BRS_station_id" => $stationId,
                            "BRS_route_id"   => $routeId,
                            "BRS_direction"  => $direction,
                            "BRS_sequence"   => $station->StopSequence
                        ]);
                    }
                    $this->terminalLog($this->getTimeTaken($routeStartTime) . "s.", true);
                }
                $this->terminalLog("$cityName took" . $this->getTimeTaken($startTime) . "s.", true);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }

    /**
     * 取得指定縣市的時刻表資料
     * @param string $cityNameEN 縣市英文名稱
     * @return mixed 時刻表資料
     * @see https://tdx.transportdata.tw/api-service/swagger/basic/#/CityBus/CityBusApi_StopOfRoute_2039
     */
    public function getBusArrivals($cityNameEN)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Bus/Schedule/City/$cityNameEN?%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 寫入公車時刻表資料
     */
    public function setBusArrivals()
    {
        try
        {
            helper(["getWeekDay", "time00To24"]);

            // 取得縣市列表
            $cities = $this->getCities();

            $skipCities = [
                "ChanghuaCounty",
                "Chiayi",
                "ChiayiCounty",
                "HsinchuCounty",
                "Hsinchu",
                "HualienCounty",
                "YilanCounty",
                "Keelung",
                "Kaohsiung",
                "MiaoliCounty",
                "NantouCounty",
                "NewTaipei",
                "PenghuCounty",
                "PingtungCounty",
                "Taoyuan"
            ];
            // 無資料縣市
            $unavailableCities = [
                "LienchiangCounty"
            ];
            // 走遍縣市列表
            foreach ($cities as $city)
            {
                if (in_array($city["C_name_EN"], $skipCities))
                {
                    $this->terminalLog("Skipped {$city["C_name_EN"]}...", true);
                    continue;
                }
                // 開始計時
                $startTime = $this->getTime();
                $week      = get_week_day(true);
                $cityId    = $city["C_id"];
                $cityName  = $city["C_name_EN"];

                // 若嘗試取得無資料的縣市則跳過
                if (in_array($cityName, $unavailableCities))
                {
                    $this->terminalLog("Skipped $cityName...", true);
                    continue;
                }
                $this->terminalLog("Running data of $cityName ... ");

                // 取得指定公車縣市的時刻表
                $arrivals = $this->getBusArrivals($cityName);

                // 走遍指定縣市的時課表
                foreach ($arrivals as $arrival)
                {
                    // 若此時課表查無 Timetables 欄位則跳過
                    if (!isset($arrival->Timetables))
                    {
                        continue;
                    }
                    $routeId   = $this->getUID($cityId, $arrival->SubRouteID);
                    $direction = $arrival->Direction;

                    // 走遍此時刻表的所有班表
                    foreach ($arrival->Timetables as $i => $timeTable)
                    {
                        if (!isset($timeTable->TripID))
                        {
                            $timeTable->TripID = $i +1;
                        }
                        $tripId = $timeTable->TripID;

                        // 走遍此班表的所有停靠時間
                        foreach ($timeTable->StopTimes as $stopTime)
                        {
                            $stationId     = $this->getUID($cityId, $stopTime->StopID);
                            $arrivalTime   = time_00_to_24($stopTime->ArrivalTime);
                            $departureTime = time_00_to_24($stopTime->DepartureTime);

                            if (!isset($timeTable->ServiceDay))
                            {
                                $timeTable->ServiceDay = new stdClass();
                                $timeTable->ServiceDay->$week = 1;
                            }
                            $this->busArrivalModel->save([
                                "BA_station_id"     => $stationId,
                                "BA_route_id"       => $routeId,
                                "BA_direction"      => $direction,
                                "BA_trip_id"        => $tripId,
                                "BA_arrival_time"   => $arrivalTime,
                                "BA_departure_time" => $departureTime,
                                "BA_arrives_today"  => $timeTable->ServiceDay->$week
                            ]);
                        }
                    }
                }
                // 印出花費時間
                $this->terminalLog($this->getTimeTaken($startTime) . "s.", true);
            }
        }
        catch (Exception $e)
        {
            $this->terminalLog($e, true, true);
            log_message("critical", $e);
        }
    }
}
