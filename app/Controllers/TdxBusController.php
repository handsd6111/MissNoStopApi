<?php

namespace App\Controllers;

use App\Models\ORM\CityModel;
use App\Models\ORM\BusCarModel;
use App\Models\ORM\BusArrivalModel;
use App\Models\ORM\BusStationModel;
use App\Models\ORM\BusRouteStationModel;
use App\Models\ORM\BusRouteModel;
use Exception;

class TdxBusController extends TdxBaseController
{
    // /v2/Bus/DisplayStopOfRoute/City/{City} 取得指定[縣市]的市區公車顯示用路線站序資料
    // /v2/Bus/StopOfRoute/City/{City} 取得指定[縣市]的市區公車路線站序資料
    /**
     * 利用 ORM Model 取得縣市列表。
     * 
     * @return object[](stdClass)
        {
            C_id => string,
            C_name_TC => string,
            C_name_EN => string,
        }
     */
    public function getCities()
    {
        try
        {
            $cityModel = new CityModel();
            $result = $cityModel->findAll();
            return $result;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

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

    public function setBusRouteStation()
    {
        try
        {
            // 因為必須使用縣市作為參數，因此首先查詢縣市列表
            $cities = $this->getCities();

            // 走遍縣市列表
            foreach ($cities as $city)
            {
                $cityName = $city["C_name_EN"];
                
                error_log("Running data of " . $cityName . "...");

                $routes               = $this->getBusRouteStation($cityName);
                $busRouteModel        = new BusRouteModel();
                $busStationModel      = new BusStationModel();
                $busRouteStationModel = new BusRouteStationModel();

                // 走遍指定縣市的路線列表
                foreach ($routes as $route)
                {
                    // 若此路線無英文名子則以路線代碼代替
                    if (!isset($route->RouteName->En))
                    {
                        $route->RouteName->En = $route->RouteUID;
                    }
                    $busRouteModel->save([
                        "BR_id"      => $route->RouteUID,
                        "BR_name_TC" => $route->RouteName->Zh_tw,
                        "BR_name_EN" => $route->RouteName->En,
                        "BR_city_id" => $route->CityCode
                    ]);

                    // 走遍指定路線的車站列表
                    foreach ($route->Stops as $station)
                    {
                        // 若此車站無英文名子則以車站代碼代替
                        if (!isset($station->StopName->En))
                        {
                            $station->StopName->En = $station->StopUID;
                        }
                        $busStationModel->save([
                            "BS_id"        => $station->StopUID,
                            "BS_name_TC"   => $station->StopName->Zh_tw,
                            "BS_name_EN"   => $station->StopName->En,
                            "BS_longitude" => $station->StopPosition->PositionLon,
                            "BS_latitude"  => $station->StopPosition->PositionLat,
                        ]);
                        $busRouteStationModel->save([
                            "BRS_station_id" => $station->StopUID,
                            "BRS_route_id"   => $route->RouteUID,
                            "BRS_sequence"   => $station->StopSequence,
                        ]);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            error_log("Found error!");
            log_message("critical", $e);
        }
    }

    /**
     * 取得指定縣市的公車時刻表資料
     */
    public function getBusArrival($cityNameEN)
    {
        try
        {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Bus/Schedule/City/$cityNameEN?&%24format=JSON";
            return $this->curlGet($url, $accessToken);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 利用 ORM Model 寫入公車時刻表及車次資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setBusArrivalAndCar()
    {
        try
        {
            // 載入「回傳今天星期幾」幫手
            helper("getWeekDay");

            // 因為必須使用縣市作為參數，因此首先查詢縣市列表
            $citieNames = $this->getCities();

            foreach ($citieNames as $city)
            {
                $cityName = $city["C_name_EN"];
                $cityId   = $city["C_id"];
                $weekday  = get_week_day(true);

                error_log("Running data of " . $cityName . "...");
                
                $arrivals = $this->getBusArrival($cityName);
                $busArrivalModel = new BusArrivalModel();
                $busCarModel     = new BusCarModel();

                foreach ($arrivals as $arrival)
                {
                    // 若無 Timetables 則跳過
                    if (!isset($arrival->Timetables))
                    {
                        continue;
                    }

                    // 行駛方向
                    $direction = $arrival->Direction;

                    // 走遍 Timetables 列表
                    foreach ($arrival->Timetables as $value)
                    {
                        $busCarModel->save([
                            "BC_id" => $cityId . "-" . $value->TripID
                        ]);
                        $busArrivalModel->save([
                            'BA_car_id'        => $cityId . "-" . $value->TripID,
                            'BA_station_id'    => $value->StopTimes[0]->StopUID,
                            'BA_arrival_time'  => $value->StopTimes[0]->ArrivalTime,
                            'BA_direction'     => $direction,
                            'BA_arrives_today' => $value->ServiceDay->$weekday,
                        ]);
                    }
                }
            }
            return true;
        }
        catch (Exception $e)
        {
            error_log("Found error!");
            log_message("critical", $e);
        }
    }
}
