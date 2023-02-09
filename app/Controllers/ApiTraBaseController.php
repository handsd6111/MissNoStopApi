<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use Exception;

class ApiTraBaseController extends ApiBaseController
{
    /**
     * 重新排列縣市資料陣列
     * @param mixed &$cities 縣市資料陣列
     */
    function restructure_cities(&$cities)
    {
        try
        {
            foreach ($cities as $i => $city)
            {
                $cities[$i] = [
                    "CityId" => $city->id,
                    "CityName" => [
                        "TC" => $city->name_TC,
                        "EN" => $city->name_EN
                    ]
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
    
    /**
     * 重新排列車站資料陣列
     * @param mixed &$stations 車站資料陣列
     */
    function restructure_stations(&$stations)
    {
        try
        {
            for ($i = 0; $i < sizeof($stations); $i++)
            {
                $station = $stations[$i];

                $stations[$i] = [
                    "StationId"   => $station->station_id,
                    "StationName" => [
                        "TC" => $station->station_name_TC,
                        "EN" => $station->station_name_EN
                    ],
                    "StationLocation" => [
                        "CityId"   => $station->city_id,
                        "Longitude" => $station->longitude,
                        "Latitude"  => $station->latitude,
                    ],
                    "Sequence" => $station->sequence
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列時刻表資料陣列
     * @param mixed &$arrivals 時刻表資料陣列
     * @param string $fromStationId 起站代碼
     */
    function restructure_arrivals(&$arrivals, $fromStationId)
    {
        try
        {
            $newArrivals = [];

            for ($i = 0; $i < sizeof($arrivals); $i += 2)
            {
                $fromData = $arrivals[$i];
                $toData   = $arrivals[$i+1];

                if ($fromData->station_id != $fromStationId) continue;

                $data = [
                    "TrainId" => $fromData->train_id,
                    "RouteId" => $fromData->route_id,
                    "RouteName" => [
                        "TC" => $fromData->route_name_TC,
                        "EN" => $fromData->route_name_EN
                    ],
                    "Schedule" => [
                        "DepartureTime" => $fromData->departure_time,
                        "ArrivalTime"   => $toData->arrival_time
                    ]
                ];
                array_push($newArrivals, $data);
            }
            usort($newArrivals, [parent::class, "cmpArrivals"]);

            $arrivals = $newArrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列車次時刻表資料陣列
     * @param mixed &$arrivals 車次時刻表資料陣列
     */
    function restructure_arrivals_by_train(&$arrivals)
    {
        try
        {
            foreach($arrivals as $i => $arrival)
            {
                $arrivals[$i] = [
                    "StationId" => $arrival->station_id,
                    "StationName" => [
                        "TC" => $arrival->station_name_TC,
                        "EN" => $arrival->station_name_EN,
                    ],
                    "Schedule" => [
                        "DepartureTime" => $arrival->departure_time,
                        "ArrivalTime" => $arrival->arrival_time
                    ]
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
