<?php

namespace App\Models;

use Exception;

class MetroModel extends BaseModel
{
    /**
     * 取得所有捷運系統資料
     * @return array 捷運系統資料
     */
    function get_systems()
    {
        try
        {
            return $this->db->table("metro_systems")
                            ->select(
                                "MST_id AS system_id,
                                MST_name_TC AS name_TC,
                                MST_name_EN AS name_EN")
                            ->orderBy("MST_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運系統所有路線的查詢類別（未執行 Query）
     * @param string $systemId 捷運系統代碼
     * @return mixed 查詢類別
     */
    function get_routes($systemId)
    {
        try
        {
            $condition = [
                "MR_system_id" => $systemId
            ];
            return $this->db->table("metro_routes")
                            ->select(
                                "MR_id AS route_id,
                                MR_name_TC AS name_TC,
                                MR_name_EN AS name_EN")
                            ->where($condition)
                            ->orderBy("MR_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運系統及路線上所有車站的查詢類別（未執行 Query）
     * @param string $routeId 路線代碼
     * @return mixed 查詢類別
     */
    function get_stations($routeId)
    {
        try
        {
            $condition = [
                "MRS_route_id" => $routeId
            ];
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MS_id = MRS_station_id")
                            ->select(
                                "MS_id AS station_id,
                                MS_name_TC AS name_TC,
                                MS_name_EN AS name_EN,
                                MS_city_id AS city_id,
                                MS_longitude AS longitude,
                                MS_latitude AS latitude,
                                MRS_sequence AS sequence")
                            ->where($condition)
                            ->orderBy("MRS_sequence");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運路線上所有車站的查詢類別（未執行 Query）
     * @param string $routeId 路線代碼
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @param int $limit 回傳數量
     * @return mixed 查詢類別
     */
    function get_nearest_station($routeId, $longitude, $latitude, $limit)
    {
        try
        {
            $condition = [
                "MR_id" => $routeId
            ];
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MS_id = MRS_station_id")
                            ->join("metro_routes", "MRS_route_id = MR_id")
                            ->select(
                                "MS_id AS station_id,
                                MS_name_TC AS name_TC,
                                MS_name_EN AS name_EN,
                                MS_city_id AS city_id,
                                MS_longitude AS longitude,
                                MS_latitude AS latitude,
                                MRS_sequence AS sequence,
                                FLOOR(
                                    SQRT(
                                        POWER(
                                            ABS(
                                                MS_longitude - $longitude
                                            ), 2
                                        ) +
                                        POWER(
                                            ABS(
                                                MS_latitude - $latitude
                                            ), 2
                                        )
                                    ) * 11100
                                ) / 100 AS MS_distance")
                            ->where($condition)
                            ->orderBy("MS_distance")
                            ->limit($limit);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定路線及代碼的捷運站序號查詢類別
     * @param string $routeId 路線代碼
     * @param string $stationId 捷運站代碼
     * @return mixed 查詢類別
     */
    function get_station_sequence($routeId, $stationId)
    {
        try
        {
            $condition = [
                "MRS_route_id"   => $routeId,
                "MRS_station_id" => $stationId
            ];
            return $this->db->table("metro_route_stations")
                            ->select("MRS_sequence AS sequence")
                            ->where($condition)
                            ->groupBy("MRS_station_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定車站及終點站方向的時刻表查詢類別（未執行 Query）
     * @param string $stationId 起站代碼
     * @param int $direction 行駛方向
     * @param int $duration 距訖站運行時間
     * @return mixed 查詢類別
     */
    function get_arrivals($sequence, $routeId, $direction, $duration)
    {
        try
        {
            $condition = [
                "MRS_sequence" => $sequence,
                "MA_route_id" => $routeId,
                "MA_direction" => $direction
            ];
            return $this->db->table("metro_arrivals")
                            ->join("metro_route_stations", "MA_station_id = MRS_station_id")
                            ->select(
                                "MA_route_id AS route_id,
                                MA_sequence AS sequence,
                                MA_arrival_time AS departure_time,
                                SEC_TO_TIME( TIME_TO_SEC( MA_arrival_time ) + $duration ) AS arrival_time")
                            ->where($condition)
                            ->orderBy("MA_sequence");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起點站及終點站之間的總運行時間查詢類別（未執行 Query）
     * @param string $fromSeq 起站序號
     * @param string $toSeq 訖站序號
     * @param int $direction 行駛方向
     * @return mixed 查詢類別
     */
    function get_duration($minSeq, $maxSeq, $routeId, $direction, $stopTime)
    {
        try
        {
            $condition = [
                "MRS_route_id" => $routeId,
                "MD_direction" => $direction
            ];
            return $this->db->table("metro_durations")
                            ->join("metro_route_stations", "MD_route_id = MRS_route_id")
                            ->select(
                                "SUM(MD_duration) + SUM(MD_stop_time) - $stopTime AS duration"
                            )
                            ->where($condition)
                            ->where("MRS_sequence BETWEEN $minSeq AND $maxSeq -1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起訖站序號的停靠時間加總
     * @param string $min 較小起訖站序號
     * @param string $max 較大起訖站序號
     * @param array $condition WHERE 資料
     * @return int 停靠時間加總
     */
    function get_stop_time($fromSeq, $routeId, $direction)
    {
        try
        {
            $condition = [
                "MRS_route_id" => $routeId,
                "MD_direction" => $direction,
                "MRS_sequence" => $fromSeq
            ];
            return $this->db->table("metro_durations")
                            ->join("metro_route_stations", "MRS_route_id = MD_route_id")
                            ->select("MD_stop_time as stop_time")
                            ->where($condition)
                            ->get()
                            ->getResult();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得起訖站皆行經的捷運路線查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param mixed 查詢類別
     */
    function get_routes_by_stations($fromStationId, $toStationId)
    {
        try
        {
            return $this->db->table("metro_route_stations")
                            ->select("MRS_route_id AS route_id")
                            ->where("MRS_station_id", $fromStationId)
                            ->orWhere("MRS_station_id", $toStationId)
                            ->having("COUNT(MRS_route_id) > 1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
