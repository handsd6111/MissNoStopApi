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
                            ->select("MST_id, MST_name_TC, MST_name_EN")
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
                            ->select("MR_id, MR_name_TC, MR_name_EN")
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
    function get_stations($systemId, $routeId)
    {
        try
        {
            $condition = [
                "MR_system_id" => $systemId,
                "MR_id"        => $routeId
            ];
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MS_id = MRS_station_id")
                            ->join("metro_routes", "MRS_route_id = MR_id")
                            ->select("MS_id, MS_name_TC, MS_name_EN, MS_sequence, MS_city_id, MS_longitude, MS_latitude")
                            ->where($condition)
                            ->orderBy("MS_id");
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
                            ->select("MA_end_station_id, MS_sequence")
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
     * @param string $stationId 車站代碼
     * @param string $endStationId 終點車站代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($stationId, $endStationId)
    {
        try
        {
            helper("getTimeMinute");
            $condition = [
                "MA_station_id"     => $stationId,
                "MA_end_station_id" => $endStationId
            ];
            // 在此使用 get_time_minute() 而不是 MySQL 內建的 NOW() 是因為時區的問題。
            return $this->db->table("metro_arrivals")
                            ->select("MA_sequence,
                                      (HOUR(MA_arrival_time) * 60 + MINUTE(MA_arrival_time)) - ". get_time_minute() ." AS MS_remain_time,
                                      MA_departure_time")
                            ->where($condition)
                            ->orderBy("MA_sequence");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定起點站及目的站所屬的捷運路線的查詢類別
     * @param string $stationId 起點站代碼
     * @param string $stationId 目的站代碼
     * @return mixed 查詢類別
     */
    function get_route_by_station($fromStationId, $toStationId)
    {
        try
        {
            $condition = [
                "MRS_station_id" => $fromStationId,
                "MRS_station_id" => $toStationId
            ];
            return$this->db->table("metro_route_stations")
                            ->select("MRS_route_id")
                            ->where($condition)
                            ->groupBy("MRS_route_id");
        }
        catch (\Exception $e)
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
    function get_nearest_station($systemId, $routeId, $longitude, $latitude)
    {
        try
        {
            $condition = [
                "MR_system_id" => $systemId,
                "MR_id"        => $routeId
            ];
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MS_id = MRS_station_id")
                            ->join("metro_routes", "MRS_route_id = MR_id")
                            ->select("MS_id,
                                      MS_name_TC,
                                      MS_name_EN,
                                      MS_sequence,
                                      MS_city_id,
                                      MS_longitude,
                                      MS_latitude,
                                      FLOOR( SQRT( POWER( ABS( MS_longitude - $longitude ), 2 ) + POWER( ABS( MS_latitude - $latitude ), 2 ) ) * 11100 ) / 100 AS MS_distance")
                            ->where($condition)
                            ->orderBy("MS_distance")
                            ->limit(1);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定起點站及終點站之間的總運行時間查詢類別（未執行 Query）
     * @param string $fromStationSeq 起點站序號
     * @param string $toStationSeq 目的站序號
     * @param string $endStationId 終點站代碼
     * @return mixed 查詢類別
     */
    function get_durations($fromStationSeq, $toStationSeq, $endStationId)
    {
        try
        {
            if ($fromStationSeq > $toStationSeq)
            {
                $condition = [
                    "MD_end_station_id =" => $endStationId,
                    "MS_sequence <="      => $fromStationSeq,
                    "MS_sequence >"       => $toStationSeq
                ];
            }
            else {
                $condition = [
                    "MD_end_station_id =" => $endStationId,
                    "MS_sequence >="      => $fromStationSeq,
                    "MS_sequence <"       => $toStationSeq
                ];
            }
            return $this->db->table("metro_durations")
                            ->join("metro_stations", "MD_station_id = MS_id")
                            ->select("SUM(MD_duration) AS MD_duration")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}
