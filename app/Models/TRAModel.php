<?php

namespace App\Models;

use Exception;

class TRAModel extends BaseModel
{
    function get_tra_cities()
    {
        try
        {
            return $this->db->table("TRA_stations")
                            ->join("cities", "C_id = RS_city_id")
                            ->select("C_id as id,
                                    C_name_TC as name_TC,
                                    c_name_EN as name_EN")
                            ->groupBy("C_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定臺鐵路線的所有臺鐵站資料查詢類別
     * @return mixed 查詢類別
     */
    function get_stations_by_city($cityId)
    {
        try
        {
            $condition = [
                "RS_city_id" => $cityId
            ];
            return $this->db->table("TRA_stations")
                            ->join("TRA_route_stations", "RRS_station_id = RS_id")
                            ->select("RS_id AS station_id,
                                      RRS_sequence AS sequence,
                                      RS_name_TC AS name_TC,
                                      RS_name_EN AS name_EN,
                                      RS_city_id AS city_id,
                                      RS_longitude AS longitude,
                                      RS_latitude AS latitude")
                            ->where($condition)
                            ->groupBy("RS_id")
                            ->orderBy("RRS_sequence");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定臺鐵路線的所有臺鐵站資料查詢類別
     * @return mixed 查詢類別
     */
    function get_stations($routeId)
    {
        try
        {
            $condition = [
                "RRS_route_id" => $routeId
            ];
            return $this->db->table("TRA_route_stations")
                            ->join("TRA_stations", "RS_id = RRS_station_id")
                            ->select("RS_id AS station_id,
                                      RRS_sequence AS sequence,
                                      RS_name_TC AS name_TC,
                                      RS_name_EN AS name_EN,
                                      RS_city_id AS city_id,
                                      RS_longitude AS longitude,
                                      RS_latitude AS latitude")
                            ->where($condition)
                            ->orderBy("RRS_sequence");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定臺鐵路線及經緯度的最近臺鐵站資料查詢類別
     * @return mixed 查詢類別
     */
    function get_nearest_station($longitude, $latitude)
    {
        try
        {
            return $this->db->table("TRA_route_stations")
                            ->join("TRA_stations", "RS_id = RRS_station_id")
                            ->select(
                               "RS_id AS station_id,
                                RS_name_TC AS name_TC,
                                RS_name_EN AS name_EN,
                                RS_city_id AS city_id,
                                RS_longitude AS longitude,
                                RS_latitude AS latitude,
                                FLOOR(
                                    SQRT(
                                        POWER(
                                            ABS(
                                                RS_longitude - $longitude
                                            ), 2
                                        ) +
                                        POWER(
                                            ABS(
                                                RS_latitude - $latitude
                                            ), 2
                                        )
                                    ) * 11100
                                ) / 100 AS RS_distance"
                            )
                            ->orderBy("RS_distance");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_arrivals_by_stations($fromStationId, $toStationId)
    {
        try
        {
            $firstArrival = $this->db->table("TRA_arrivals")
                                     ->select("RA_train_id,
                                               RA_station_id,
                                               RA_arrival_time,
                                               RA_departure_time")
                                     ->where("RA_station_id", $fromStationId)
                                     ->orWhere("RA_station_id", $toStationId)
                                     ->groupBy("RA_train_id")
                                     ->having("COUNT(RA_train_id) > 1");
            return $this->db->newQuery()
                            ->fromSubquery($firstArrival, "first_arrival")
                            ->join("TRA_arrivals", "TRA_arrivals.RA_train_id = first_arrival.RA_train_id")
                            ->join("TRA_stations", "RS_id = TRA_arrivals.RA_station_id")
                            ->join("TRA_route_stations", "RRS_station_id = TRA_arrivals.RA_station_id")
                            ->join("TRA_routes", "RR_id = RRS_route_id")
                            ->select("RR_id as route_id,
                                      RR_name_TC as route_name_TC,
                                      RR_name_EN as route_name_EN,
                                      TRA_arrivals.RA_train_id as train_id,
                                      TRA_arrivals.RA_station_id as station_id,
                                      TRA_arrivals.RA_arrival_time as arrival_time,
                                      TRA_arrivals.RA_departure_time as departure_time")
                            ->where("TRA_arrivals.RA_station_id", $fromStationId)
                            ->orWhere("TRA_arrivals.RA_station_id", $toStationId)
                            ->orderBy("TRA_arrivals.RA_train_id,
                                       TRA_arrivals.RA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_arrivals_by_train($trainId)
    {
        try
        {
            return $this->db->table("TRA_arrivals")
                            ->join("TRA_stations", "RS_id = RA_station_id")
                            ->select(
                                "RS_id as station_id,
                                RS_name_TC as station_name_TC,
                                RS_name_EN as station_name_EN,
                                RA_arrival_time as arrival_time,
                                RA_departure_time as departure_time"
                            )
                            ->where("RA_train_id", $trainId)
                            ->orderBy("RA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }



    // /**
    //  * 取得行經指定臺鐵起訖站的所有車次
    //  * @param string $fromStationId 起站代碼
    //  * @param string $toStringId 訖站代碼
    //  * @param int $direction 行車方向（0：南下；1：北上）
    //  * @return mixed 查詢類別
    //  */
    // function get_trains_by_stations($fromStationId, $toStationId, $direction)
    // {
    //     try
    //     {
    //         $condition1 = [
    //             "RT_departure_date" => date("Y-m-d H:i:s"),
    //             "RA_station_id"  => $fromStationId,
    //             "RA_direction" => $direction
    //         ];
    //         $condition2 = [
    //             "RT_departure_date" => date("Y-m-d H:i:s"),
    //             "RA_station_id"  => $toStationId,
    //             "RA_direction" => $direction
    //         ];
    //         return $this->db->table("TRA_arrivals")
    //                         ->join("TRA_trains", "RT_id = RA_train_id")
    //                         ->select("RA_train_id")
    //                         ->where($condition1)
    //                         ->orWhere($condition2)
    //                         ->groupBy("RA_train_id")
    //                         ->having("COUNT(RA_train_id) > 1");
    //     }
    //     catch (Exception $e)
    //     {
    //         log_message("critical", $e->getMessage());
    //         throw $e;
    //     }
    // }

    /**
     * 取得指定臺鐵起訖站的時刻表資料查詢類別
     * @return mixed 查詢類別
     */
    // function get_arrivals($trainId, $fromStationId, $toStationId)
    // {
    //     try
    //     {
    //         $stations = [
    //             $fromStationId,
    //             $toStationId
    //         ];
    //         return $this->db->table("TRA_arrivals")
    //                         ->join("TRA_route_stations", "RA_station_id = RRS_station_id")
    //                         ->join("TRA_routes", "RRS_route_id = RR_id")
    //                         ->select("RA_train_id AS train_id,
    //                                   RA_station_id AS station_id,
    //                                   RR_id AS route_id,
    //                                   RR_name_TC AS route_name_TC,
    //                                   RR_name_EN AS route_name_EN,
    //                                   RA_arrival_time AS arrival_time")
    //                         ->where("RA_train_id", $trainId)
    //                         ->whereIn("RA_station_id", $stations)
    //                         ->orderBy("RA_arrival_time");
    //     }
    //     catch (Exception $e)
    //     {
    //         log_message("critical", $e->getMessage());
    //         return $this->send_response([], 500, "Exception error");
    //     }
    // }
}