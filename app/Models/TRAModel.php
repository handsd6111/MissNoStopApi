<?php

namespace App\Models;

use Exception;

class TRAModel extends BaseModel
{
    /**
     * 取得所有臺鐵路線資料查詢類別
     * @return mixed 查詢類別
     */
    function get_routes()
    {
        try
        {
            return $this->db->table("TRA_routes")
                            ->select("RR_id AS route_id,
                                      RR_name_TC AS name_TC,
                                      RR_name_EN AS name_EN");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
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
                            ->select("RRS_sequence,
                                      RS_id AS station_id,
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
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 取得指定臺鐵路線及經緯度的最近臺鐵站資料查詢類別
     * @return mixed 查詢類別
     */
    function get_nearest_station($routeId, $longitude, $latitude)
    {
        try
        {
            $condition = [
                "RRS_route_id" => $routeId
            ];
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
                            ->where($condition)
                            ->orderBy("RS_distance")
                            ->limit(1);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 取得行經指定臺鐵起訖站的所有車次
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @param int $direction 行車方向（0：南下；1：北上）
     * @return mixed 查詢類別
     */
    function get_trains_by_stations($fromStationId, $toStationId, $direction)
    {
        try
        {
            $condition1 = [
                "RA_train_id"  => $fromStationId,
                "RA_direction" => $direction
            ];
            $condition2 = [
                "RA_train_id"  => $toStationId,
                "RA_direction" => $direction
            ];
            return $this->db->table("TRA_arrivals")
                            ->select("RA_train_id")
                            ->where($condition1)
                            ->orWhere($condition2)
                            ->groupBy("RA_train_id")
                            ->having("COUNT(RA_train_id) > 1");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定臺鐵起訖站的時刻表資料查詢類別
     * @return mixed 查詢類別
     */
    function get_arrivals($trainId, $fromStationId, $toStationId)
    {
        try
        {
            $stations = [
                $fromStationId,
                $toStationId
            ];
            return $this->db->table("TRA_arrivals")
                            ->select("RA_train_id AS train_id,
                                      RA_station_id AS station_id,
                                      RA_arrival_time AS arrival_time")
                            ->where("RA_train_id", $trainId)
                            ->whereIn("RA_station_id", $stations)
                            ->orderBy("RA_arrival_time");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }
}