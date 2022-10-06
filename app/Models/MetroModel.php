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
            log_message("critical", $e->getMessage());
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
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定捷運系統及路線上所有車站的查詢類別（未執行 Query）
     * @param string $systemId 捷運系統代碼
     * @param string $routeId 路線代碼
     * @return mixed 查詢類別
     */
    function get_stations($routeId)
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
                                MS_sequence AS sequence,
                                MS_city_id AS city_id,
                                MS_longitude AS longitude,
                                MS_latitude AS latitude")
                            ->where($condition)
                            ->orderBy("MS_sequence");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
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
                                MS_sequence AS sequence,
                                MS_city_id AS city_id,
                                MS_longitude AS longitude,
                                MS_latitude AS latitude,
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
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定捷運站資料查詢類別
     * @param string $stationId 捷運站代碼
     * @return mixed 查詢類別
     */
    function get_station_sequence($stationId)
    {
        try
        {
            $condition = [
                "MS_id" => $stationId
            ];
            return $this->db->table("metro_stations")
                            ->select("MS_sequence")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定起點站及目的站都能開往的所有終點站查詢類別
     * @param string $stationId 起點站代碼
     * @param string $stationId 目的站代碼
     * @return mixed 查詢類別
     */
    function get_end_stations($fromStationId, $toStationId)
    {
        try
        {
            $condition1 = [
                "MA_station_id" => $fromStationId
            ];
            $condition2 = [
                "MA_station_id" => $toStationId
            ];
            return $this->db->table("metro_arrivals")
                            ->join("metro_stations", "MS_id = MA_end_station_id")
                            ->select(
                                "MA_end_station_id AS end_station_id,
                                MS_sequence AS sequence")
                            ->where($condition1)
                            ->orWhere($condition2)
                            ->groupBy("MA_end_station_id")
                            ->orderBy("MS_sequence");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定車站及終點站方向的時刻表查詢類別（未執行 Query）
     * @param string $stationId 起站代碼
     * @param string $endStationId 終點車站代碼
     * @param int $duration 距訖站運行時間
     * @return mixed 查詢類別
     */
    function get_arrivals($stationId, $endStationId, $duration)
    {
        try
        {
            $condition = [
                "MA_end_station_id" => $endStationId,
                "MA_station_id"     => $stationId
            ];
            return $this->db->table("metro_arrivals")
                            ->select(
                                "MA_sequence AS sequence,
                                MA_departure_time AS departure_time,
                                SEC_TO_TIME( TIME_TO_SEC( MA_departure_time ) + $duration ) AS arrival_time")
                            ->where($condition)
                            ->orderBy("MA_arrival_time");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定起點站及終點站之間的總運行時間查詢類別（未執行 Query）
     * @param string $fromStationSeq 起站序號
     * @param string $toStationSeq 訖站序號
     * @param string $endStationId 終點站代碼
     * @return mixed 查詢類別
     */
    function get_durations($fromStationSeq, $toStationSeq, $endStationId)
    {
        try
        {
            $condition1 = [
                "MD_end_station_id" => $endStationId
            ];
            if ($fromStationSeq > $toStationSeq)
            {
                $condition2 = [
                    "MS_sequence <=" => $fromStationSeq,
                    "MS_sequence >"  => $toStationSeq
                ];
            }
            else
            {
                $condition2 = [
                    "MS_sequence >=" => $fromStationSeq,
                    "MS_sequence <"  => $toStationSeq
                ];
            }
            return $this->db->table("metro_durations")
                            ->join("metro_stations", "MD_station_id = MS_id")
                            ->select("SUM(MD_duration) AS duration")
                            ->where($condition1)
                            ->where($condition2);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}
