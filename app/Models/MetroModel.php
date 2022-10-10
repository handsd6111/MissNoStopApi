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
     * 取得指定捷運系統所有路線的查詢類別
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
     * 取得指定捷運系統及路線上所有車站的查詢類別
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
     * 取得指定捷運路線上所有車站的查詢類別
     * @param string $lineId 路線代碼
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @param int $limit 回傳數量
     * @return mixed 查詢類別
     */
    function get_nearest_station($lineId, $longitude, $latitude, $limit)
    {
        try
        {
            $condition = [
                "MR_id" => $lineId
            ];
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MRS_station_id = MS_id")
                            ->join("metro_routes", "MR_id = MRS_route_id")
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
     * 取得指定捷運站在路線上的序號查詢類別
     * @param string $stationId 捷運站代碼
     * @return mixed 查詢類別
     */
    function get_route_sequence($stationId)
    {
        try
        {
            $condition = [
                "MRS_station_id" => $stationId
            ];
            return $this->db->table("metro_route_stations")
                            ->select("MRS_sequence AS sequence")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運站在子路線上的序號查詢類別
     * @param string $stationId 捷運站代碼
     * @return mixed 查詢類別
     */
    function get_sub_route_sequence($stationId, $direction)
    {
        try
        {
            $condition = [
                "MSRS_station_id" => $stationId,
                "MSRS_direction"  => $direction
            ];
            return $this->db->table("metro_sub_route_stations")
                            ->select("MSRS_sequence AS sequence")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得時刻表查詢類別
     * @param string $fromStationId 車站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @param int $duration 總運行時間
     * @return mixed 查詢類別
     */
    function get_arrivals($fromStationId, $subRouteId, $direction, $duration)
    {
        try
        {
            $condition = [
                "MA_station_id"   => $fromStationId,
                "MA_sub_route_id" => $subRouteId,
                "MA_direction"    => $direction
            ];
            return $this->db->table("metro_arrivals")
                            ->select(
                                "MA_sub_route_id AS sub_route_id,
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
     * 取得總運行時間查詢類別
     * @param string $minSeq 較小起訖站序號
     * @param string $maxSeq 較大起訖站序號
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @param int $stopTime 停靠時間
     * @return mixed 查詢類別
     */
    function get_duration($minSeq, $maxSeq, $subRouteId, $direction, $stopTime)
    {
        try
        {
            $condition = [
                "MD_sub_route_id" => $subRouteId,
                "MD_direction"    => $direction
            ];
            return $this->db->table("metro_durations")
                            ->join("metro_sub_routes", "MSR_id = MD_sub_route_id")
                            ->join("metro_stations", "MS_id = MD_station_id")
                            ->join("metro_sub_route_stations", "MSRS_station_id = MS_id")
                            ->select(
                                "SUM(MD_duration) + SUM(MD_stop_time) - $stopTime AS duration"
                            )
                            ->where($condition)
                            ->where("MSRS_sequence BETWEEN $minSeq AND $maxSeq -1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起站、子路線及行駛方向的停靠時間查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @return mixed 查詢類別
     */
    function get_stop_time($fromStationId, $subRouteId, $direction)
    {
        try
        {
            $condition = [
                "MD_sub_route_id"   => $subRouteId,
                "MD_direction"  => $direction,
                "MD_station_id" => $fromStationId
            ];
            return $this->db->table("metro_durations")
                            ->select("MD_stop_time as stop_time")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得起訖站皆行經的捷運子路線查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param int $direction 行駛方向
     * @param mixed 查詢類別
     */
    function get_sub_routes_by_stations($fromStationId, $toStationId, $direction)
    {
        try
        {
            return $this->db->table("metro_durations")
                            ->select("MD_sub_route_id AS sub_route_id")
                            ->where("MD_direction", $direction)
                            ->groupStart()
                                ->where("MD_station_id", $fromStationId)
                                ->orWhere("MD_station_id", $toStationId)
                            ->groupEnd()
                            ->groupBy("MD_sub_route_id")
                            ->having("COUNT(MD_station_id) > 1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
