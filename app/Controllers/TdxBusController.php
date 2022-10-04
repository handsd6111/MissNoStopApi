<?php

namespace App\Controllers;

use App\Models\BusModel;
use App\Models\ORM\BusArrivalModel;
use App\Models\ORM\BusRouteStationModel;
use App\Models\ORM\BusRouteModel;
use App\Models\ORM\BusStationModel;
use App\Models\ORM\BusTripModel;
use App\Models\ORM\CityModel;
use Exception;

class TdxBusController extends TdxBaseController
{
    /**
     * 統一載入模型
     * @return void 不回傳值
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
            $this->busTripModel         = new BusTripModel();
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
     * @return void 不回傳值
     */
    public function setBusRouteStation()
    {
        try
        {
            // 取得縣市列表
            $cities = $this->getCities();

            // 走遍縣市列表
            foreach ($cities as $city)
            {
                error_log("Running data of {$city["C_name_EN"]} ...");

                $cityId = $city["C_id"];
                $routes = $this->getBusRouteStation($city["C_name_EN"]);

                // 走遍指定縣市的路線列表
                foreach ($routes as $route)
                {
                    // 若無英文路名則以中文路名代替
                    if (!isset($route->SubRouteName->En))
                    {
                        $route->SubRouteName->En = $route->SubRouteName->Zh_tw;
                    }

                    $routeId = $this->getUID($cityId, $route->SubRouteID);

                    $this->busRouteModel->save([
                        "BR_id"      => $routeId,
                        "BR_name_TC" => $route->SubRouteName->Zh_tw,
                        "BR_name_EN" => $route->SubRouteName->En
                    ]);

                    // 走遍指定路線的車站列表
                    foreach ($route->Stops as $station)
                    {
                        // 若無英文站名則以中文站名代替
                        if (!isset($station->StopName->En))
                        {
                            $station->StopName->En = $station->StopName->Zh_tw;
                        }
                        // 若無車站代碼則直接跳過
                        if (!isset($station->StopID))
                        {
                            continue;
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
                            "BRS_direction"  => $route->Direction,
                            "BRS_sequence"   => $station->StopSequence
                        ]);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }

    /**
     * /v2/Bus/EstimatedTimeOfArrival/City/{City} 接下來即將抵達的多台公車及剩餘時間
     */

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
     * 
     */
    public function setBusArrivals()
    {
        
        try
        {
            // 取得縣市列表
            // $cities = $this->getCities();
            $cities = [
                [
                    "C_id" => "TPE",
                    "C_name_EN" => "Taipei"
                ]
            ];

            $finishedCities = [

            ];

            // 走遍縣市列表
            foreach ($cities as $city)
            {
                $cityName = $city["C_name_EN"];
                
                helper("getWeekDay");

                error_log("Running data of $cityName ...");

                if (in_array($cityName, $finishedCities))
                {
                    error_log("skipped $cityName ...");
                    continue;
                }

                $cityId   = $city["C_id"];
                $arrivals = $this->getBusArrivals($cityName);
                $week     = get_week_day(true);

                // 走遍指定縣市的時課表
                foreach ($arrivals as $arrival)
                {
                    // 若此時課表查無 Timetables 欄位則跳過
                    if (!isset($arrival->Timetables))
                    {
                        continue;
                    }

                    // 走遍此時刻表的所有班表
                    foreach ($arrival->Timetables as $timeTable)
                    {
                        $tripId = $this->getUID($cityId, $timeTable->TripID);

                        $this->busTripModel->save([
                            "BT_id" => $tripId
                        ]);

                        // 走遍此班表的所有停靠時間
                        foreach ($timeTable->StopTimes as $stopTime)
                        {
                            $this->busArrivalModel->save([
                                "BA_trip_id"       => $tripId,
                                "BA_station_id"    => $this->getUID($cityId, $stopTime->StopID),
                                "BA_direction"     => $arrival->Direction,
                                "BA_sequence"      => $stopTime->StopSequence,
                                "BA_arrival_time"  => $stopTime->ArrivalTime,
                                "BA_arrives_today" => $timeTable->ServiceDay->$week
                            ]);
                        }
                    }
                }
            }
        }
        catch (Exception $e)
        {
            error_log($e);
            log_message("critical", $e);
        }
    }
}
