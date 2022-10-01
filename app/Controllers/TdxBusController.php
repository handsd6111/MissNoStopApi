<?php

namespace App\Controllers;

use App\Models\BusModel;
use App\Models\ORM\BusDuplicatedStations;
use App\Models\ORM\BusRouteStationModel;
use App\Models\ORM\BusRouteModel;
use App\Models\ORM\BusStationModel;
use App\Models\ORM\CityModel;
use Exception;

class TdxBusController extends TdxBaseController
{
    public function __construct()
    {
        try
        {
            $this->cityModel             = new CityModel();
            $this->busModel              = new BusModel();
            $this->busStationModel       = new BusStationModel();
            $this->busDuplicatedStations = new BusDuplicatedStations();
            $this->busRouteStationModel  = new BusRouteStationModel();
            $this->busRouteModel         = new BusRouteModel();
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
     * 排除重複車站代碼的情況
     * @param string $stationId 車站代碼
     * @param string $routeId 路線代碼
     * @param int $sequence 序號
     * @return string 可使用的車站代碼
     */
    public function duplicationHandeller($stationId, $routeId, $sequence)
    {
        try
        {
            // SELECT `BRS_station_id`, `BS_name_TC`, `BRS_sequence` FROM `bus_route_stations`, `bus_stations` WHERE `BS_id` = `BRS_station_id` AND `BRS_route_id` = 'CHA-0625' ORDER BY `BRS_sequence`; 
            // 否則檢查一公車路線是否已有指定序號的資料
            $query1 = $this->busModel->check_duplicated_sequence($routeId, $sequence)->get()->getResult();

            // 若是則將重複資訊寫入「公車重複車站」資料表，並回傳所查詢到的車站代碼
            if ($query1)
            {
                $this->busDuplicatedStations->save([
                    "BDS_station_id"    => $query1[0]->station_id,
                    "BDS_duplicated_id" => $stationId
                ]);
                return $query1[0]->station_id;
            }
            
            // 查詢指定車站代碼是否有重複的紀錄
            $query2 = $this->busModel->check_duplicated_station($stationId)->get()->getResult();

            // 若是則將欲寫入「公車車站」及「公車路線車站」的車站代碼改為查詢到的車站代碼
            if ($query2)
            {
                return $query2[0]->station_id;
            }

            // 否則回傳原有車站代碼
            return $stationId;
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

                $routes = $this->getBusRouteStation($cityName);

                // 走遍指定縣市的路線列表
                foreach ($routes as $route)
                {
                    if ($route->Direction != 0)
                    {
                        continue;
                    }

                    $routeId = $cityId . "-" . $route->SubRouteID;

                    // 若此路線無英文名子則以路線代碼代替
                    if (!isset($route->SubRouteName->En))
                    {
                        $route->SubRouteName->En = $routeId;
                    }


                    $this->busRouteModel->save([
                        "BR_id"      => $routeId,
                        "BR_name_TC" => $route->SubRouteName->Zh_tw,
                        "BR_name_EN" => $route->SubRouteName->En
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

                        $stationId = $this->duplicationHandeller("$cityId-$station->StopID", $routeId, $station->StopSequence);

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
                            "BRS_sequence"   => $station->StopSequence
                        ]);
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
