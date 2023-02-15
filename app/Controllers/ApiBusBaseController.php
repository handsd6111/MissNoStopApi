<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use Exception;

class ApiBusBaseController extends ApiBaseController
{
    /**
     * 重新排列路線資料陣列
     * @param mixed &$routes 路線資料陣列
     */
    function restructure_routes(&$routes)
    {
        try
        {
            foreach ($routes as $key => $value)
            {
                $routes[$key] = [
                    "RouteId"   => $value->route_id,
                    "RouteName" => [
                        "TC" => $value->station_name_TC,
                        "EN" => $value->station_name_EN
                    ],
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
                        "CityId"    => $station->city_id,
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
     * 重新排列最近車站資料陣列
     * @param mixed &$stations 最近車站資料陣列
     */
    function restructure_nearest_stations(&$stations)
    {
        try
        {
            for ($i = 0; $i < sizeof($stations); $i++)
            {
                $station = $stations[$i];

                $stations[$i] = [
                    "RouteId"   => $station->route_id,
                    "RouteName" => [
                        "TC" => $station->route_name_TC,
                        "EN" => $station->route_name_EN,
                    ],
                    "StationId"   => $station->station_id,
                    "StationName" => [
                        "TC" => $station->station_name_TC,
                        "EN" => $station->station_name_EN
                    ],
                    "StationLocation" => [
                        "CityId"    => $station->city_id,
                        "Longitude" => $station->longitude,
                        "Latitude"  => $station->latitude,
                    ],
                    "Direction" => $station->direction
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
     * @param array &$arrivals 時刻表資料陣列
     * @param array &$fromArrivals 起站時刻表資料陣列
     * @param array &$toArrivals 訖站時刻表資料陣列
     */
    function restructure_arrivals(&$arrivals)
    {
        try
        {
            $newArrivals = [
                "RouteId"   => $arrivals[0]->route_id,
                "RouteName" => [
                    "TC" => $arrivals[0]->route_name_TC,
                    "EN" => $arrivals[0]->route_name_EN
                ],
                "FromStationId"   => $arrivals[0]->station_id,
                "FromStationName" => [
                    "TC" => $arrivals[0]->station_name_TC,
                    "EN" => $arrivals[0]->station_name_EN,
                ],
                "ToStationId"   => $arrivals[1]->station_id,
                "ToStationName" => [
                    "TC" => $arrivals[1]->station_name_TC,
                    "EN" => $arrivals[1]->station_name_EN,
                ],
                "Schedule" => []
            ];
            for ($i = 0; $i < sizeof($arrivals); $i += 2)
            {
                $fromArrival = $arrivals[$i];
                $toArrival   = $arrivals[$i + 1];

                $schedule = [
                    "DepartureTime" => $fromArrival->departure_time,
                    "ArrivalTime"   => $toArrival->arrival_time
                ];
                array_push($newArrivals["Schedule"], $schedule);
            }
            usort($newArrivals["Schedule"], function ($a, $b)
            {
                return $a["DepartureTime"] <=> $b["DepartureTime"];
            });
            $arrivals = $newArrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    
    /**
     * 重新排列路線時刻表資料陣列
     * @param array &$arrivals 路線時刻表資料陣列
     */
    function restructure_arrivals_by_route(&$arrivals)
    {
        try
        {
            foreach($arrivals as $i => $arrival)
            {
                $arrivals[$i] = [
                    "StationId"   => $arrival->station_id,
                    "StationName" => [
                        "TC" => $arrival->station_name_TC,
                        "EN" => $arrival->station_name_EN,
                    ],
                    "Schedule" => [
                        "DepartureTime" => $arrival->arrival_time,
                        "ArrivalTime"   => $arrival->arrival_time
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
