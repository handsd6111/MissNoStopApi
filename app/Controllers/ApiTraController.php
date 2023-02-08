<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use App\Models\TRAModel;
use Exception;

class ApiTraController extends ApiBaseController
{
    public $TRAModel;

    // 載入模型
    function __construct()
    {
        try
        {
            $this->TRAModel = new TRAModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
        }
    }

    function get_tra_cities()
    {
        try
        {
            $cities = $this->TRAModel->get_tra_cities()->get()->getResult();

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

            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定縣市的所有臺鐵站資料
     * @param string $cityId 縣市代碼
     * @return array 臺鐵站資料
     */
    function get_stations_by_city($cityId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $cityId, parent::CITY_ID_LENGTH))
            {
                return $this->send_response([], 400, (array) $this->validator->getErrors());
            }

            // 取得高鐵所有車站資料
            $stations = $this->TRAModel->get_stations_by_city($cityId)->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($stations);

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定臺鐵路線的所有臺鐵站資料
     * @param string 路線代碼
     * @return array 臺鐵站資料
     */
    // function get_tra_stations($routeId)
    // {
    //     try
    //     {
    //         // 驗證參數
    //         if (!$this->validate_param("RouteId", $routeId, parent::TRA_ROUTE_ID_LENGTH))
    //         {
    //             return $this->send_response([], 400, (array) $this->validator->getErrors());
    //         }

    //         // 取得高鐵所有車站資料
    //         $stations = $this->TRAModel->get_stations($routeId)->get()->getResult();

    //         // 重新排列資料
    //         $this->restructure_stations($stations);

    //         // 回傳資料
    //         return $this->send_response($stations);
    //     }
    //     catch (Exception $e)
    //     {
    //         log_message("critical", $e->getMessage());
    //         return $this->send_response([], 500, lang("Exception.exception"));
    //     }
    // }

    /**
     * 取得指定臺鐵路線及經緯度的最近臺鐵站資料
     * @param string 經度
     * @param string 緯度
     * @param int $limit 回傳數量
     * @return array 最近臺鐵站資料
     */
    function get_tra_nearest_station($longitude, $latitude)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得臺鐵所有車站資料
            $station = $this->TRAModel->get_nearest_station($longitude, $latitude)->get(1)->getResult();

            // 重新排列資料
            $this->restructure_stations($station);

            // 回傳資料
            return $this->send_response($station);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定臺鐵起訖站的時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 時刻表資料
     */
    function get_tra_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::TRA_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::TRA_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            $schedules = $this->TRAModel->get_arrivals_by_stations($fromStationId, $toStationId)->get()->getResult();

            $arrivals = [];

            for ($i = 0; $i < sizeof($schedules); $i += 2)
            {
                $fromData = $schedules[$i];
                $toData   = $schedules[$i+1];

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
                array_push($arrivals, $data);
            }

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    function get_tra_arrivals_by_train($trainId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("TrainId", $trainId, parent::TRA_TRAIN_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }
            $arrivals = $this->TRAModel->get_arrivals_by_train($trainId)->get()->getResult();

            foreach($arrivals as $i => $arrival)
            {
                $arrivals[$i] = [
                    "StationId" => $arrival->station_id,
                    "StationName" => [
                        "TC" => $arrival->station_name_TC,
                        "EN" => $arrival->station_name_EN,
                    ],
                    "Schedule" => [
                        "ArrivalTime" => $arrival->arrival_time,
                        "DepartureTime" => $arrival->departure_time
                    ]
                ];
            }
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
