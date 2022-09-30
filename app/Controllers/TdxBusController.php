<?php

namespace App\Controllers;

use App\Models\ORM\CityModel;
use App\Models\ORM\BusStationModel;
use App\Models\ORM\BusRouteStationModel;
use App\Models\ORM\BusRouteModel;
use Exception;

class TdxBusController extends TdxBaseController
{
    /**
     * 取得縣市列表
     * @return array 縣市列表
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
            // 因為必須使用縣市作為參數，因此首先查詢縣市列表
            // $cities = $this->getCities();

            $cities = [
                0 => [
                    "C_id" => "CHA",
                    "C_name_EN" => "ChanghuaCounty"
                ]
            ];

            // $finishedCities = [
            // ];

            // 走遍縣市列表
            foreach ($cities as $city)
            {
                $cityName = $city["C_name_EN"];
                $cityId   = $city["C_id"];

                // if (in_array($cityName, $finishedCities))
                // {
                //     error_log("skipped $cityName");
                //     continue;
                // }

                error_log("Running data of $cityName ...");

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

                    $routeId = $cityId . "-" . $route->RouteID;

                    $busRouteModel->save([
                        "BR_id"      => $routeId,
                        "BR_name_TC" => $route->RouteName->Zh_tw,
                        "BR_name_EN" => $route->RouteName->En
                    ]);

                    // 走遍指定路線的車站列表
                    foreach ($route->Stops as $station)
                    {
                        // 若此車站無英文站名則以中文站名代替
                        if (!isset($station->StopName->En))
                        {
                            $station->StopName->En = $station->StopName->Zh_tw;
                        }
                        // 若無車站代碼則直接跳過
                        if (!isset($station->StationID))
                        {
                            continue;
                        }

                        $stationId = $cityId . "-" . $station->StopID;

                        $busStationModel->save([
                            "BS_id"        => $stationId,
                            "BS_name_TC"   => $station->StopName->Zh_tw,
                            "BS_name_EN"   => $station->StopName->En,
                            "BS_city_id"   => $cityId,
                            "BS_longitude" => $station->StopPosition->PositionLon,
                            "BS_latitude"  => $station->StopPosition->PositionLat
                        ]);
                        $busRouteStationModel->save([
                            "BRS_station_id" => $stationId,
                            "BRS_route_id"   => $routeId,
                            "BRS_sequence"   => $station->StopSequence
                        ]);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            error_log("Found error!: " . $e);
            log_message("critical", $e);
        }
    }
}
