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
                            ->select("*")
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
     * 取得指定車站及終點站方向的時刻表查詢類別（未執行 Query）
     * @param string $stationId 車站代碼
     * @param string $endStationId 終點車站代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($stationId, $endStationId)
    {
        try
        {
            $condition = [
                "MA_station_id"     => $stationId,
                "MA_end_station_id" => $endStationId
            ];
            return $this->db->table("metro_arrivals")
                            ->select("MA_sequence, MA_arrival_time, MA_departure_time")
                            ->where($condition)
                            ->orderBy("MA_sequence");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}
