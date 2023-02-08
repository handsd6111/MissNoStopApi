<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use Exception;

class ApiThsrBaseController extends ApiBaseController
{
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

                $schedule = [
                    "TrainId" => $fromData->train_id,
                    "Schedule" => [
                        "DepartureTime" => $fromData->departure_time,
                        "ArrivalTime"   => $toData->arrival_time
                    ]
                ];
                array_push($newArrivals, $schedule);
            }
            $arrivals = $newArrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

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