<?php

namespace App\Models;

use Exception;

class MetroModel extends BaseModel
{
    /**
     * 取得所有捷運系統資料
     * @return mixed 捷運系統資料
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
     * 取得指定路線的查詢類別
     * @param string $routeId 捷運路線代碼
     * @return mixed 查詢類別
     */
    function get_route($routeId)
    {
        try
        {
            return $this->db->table("metro_routes")
                            ->select(
                                "MR_id AS route_id,
                                MR_name_TC AS name_TC,
                                MR_name_EN AS name_EN")
                            ->where("MR_id", $routeId)
                            ->orderBy("MR_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運系統的所有路線查詢類別
     * @param string $systemId 捷運系統代碼
     * @return mixed 查詢類別
     */
    function get_routes($systemId)
    {
        try
        {
            return $this->db->table("metro_routes")
                            ->select(
                                "MR_id AS route_id,
                                MR_name_TC AS name_TC,
                                MR_name_EN AS name_EN")
                            ->where("MR_system_id",$systemId)
                            ->orderBy("MR_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定子路線代碼的子路線資料
     */
    function get_sub_route($subRouteId)
    {
        try
        {
            return $this->db->table("metro_sub_routes")
                            ->select(
                                "MSR_id AS sub_route_id,
                                MSR_name_TC AS name_TC,
                                MSR_name_EN AS name_EN"
                                )
                            ->where("MSR_id", $subRouteId);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_sub_routes($stationId)
    {
        try
        {
            return $this->db->table("metro_sub_route_stations")
                            ->select("MSRS_sub_route_id AS sub_route_id")
                            ->where("MSRS_station_id", $stationId)
                            ->groupBy("MSRS_sub_route_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_station($stationId)
    {
        try
        {
            $condition = [
                "MS_id" => $stationId
            ];
            return $this->db->table("metro_stations")
                            ->select(
                                "MS_id AS station_id,
                                MS_name_TC AS station_name_TC,
                                MS_name_EN AS station_name_EN,
                                MS_city_id AS city_id,
                                MS_longitude AS longitude,
                                MS_latitude AS latitude"
                            )
                            ->where($condition);
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
                                MS_name_TC AS station_name_TC,
                                MS_name_EN AS station_name_EN,
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
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @return mixed 查詢類別
     */
    function get_nearest_station($longitude, $latitude)
    {
        try
        {
            return $this->db->table("metro_stations")
                            ->join("metro_route_stations", "MRS_station_id = MS_id")
                            ->join("metro_routes", "MR_id = MRS_route_id")
                            ->join("metro_systems", "MST_id = MR_system_id")
                            ->select(
                                "MST_id AS system_id,
                                MST_name_TC AS system_name_TC,
                                MST_name_EN AS system_name_EN,
                                MR_id AS route_id,
                                MR_name_TC AS route_name_TC,
                                MR_name_EN AS route_name_EN,
                                MS_id AS station_id,
                                MS_name_TC AS station_name_TC,
                                MS_name_EN AS station_name_EN,
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
                            ->orderBy("MS_distance");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得時刻表查詢類別
     * @param string $stationId 車站代碼
     * @param int $direction 行駛方向
     * @param array $subRoutes 子路線陣列
     * @param string $arrivalTime 目前時間
     * @return mixed 查詢類別
     */
    function get_arrivals_minimum($stationId, $direction, $subRoutes, $arrivalTime = null)
    {
        try
        {
            $condition = [
                "MA_station_id"   => $stationId,
                "MA_direction"    => $direction
            ];
            if ($arrivalTime != null)
            {
                $condition["MA_arrival_time >="] = $arrivalTime;
            }
            return $this->db->table("metro_arrivals")
                            ->join("metro_sub_routes", "MSR_id = MA_sub_route_id")
                            ->select(
                                "MSR_route_id AS route_id,
                                MA_sub_route_id AS sub_route_id,
                                MA_arrival_time AS departure_time"
                            )
                            ->where($condition)
                            ->whereIn("MA_sub_route_id", $subRoutes)
                            ->orderBy("MA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_arrivals_by_route($routeId, $direction, $time)
    {
        try
        {
            $ascendance = "ASC";

            if ($direction == 1)
            {
                $ascendance = "DESC";
            }
            return $this->db->table("metro_arrivals")
                            ->select(
                                "MS_id as station_id,
                                MS_name_TC as station_name_TC,
                                MS_name_EN as station_name_EN,
                                MA_arrival_time as arrival_time"
                            )
                            ->join("metro_route_stations", "MRS_station_id = MA_station_id")
                            ->join("metro_stations", "MS_id = MA_station_id")
                            ->where("MRS_route_id", $routeId)
                            ->where("MA_direction", $direction)
                            ->where("MA_arrival_time >=", $time)
                            ->orderBy("MRS_sequence $ascendance")
                            ->groupBy("MS_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得時刻表查詢類別
     * @param string $fromStationId 車站代碼
     * @param int $direction 行駛方向
     * @param array $subRouteId 子路線代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($fromStationId, $direction, $subRoutes, $arrivalTime = null)
    {
        try
        {
            $condition = [
                "MA_station_id"   => $fromStationId,
                "MA_direction"    => $direction
            ];
            if ($arrivalTime != null)
            {
                $condition["MA_arrival_time >="] = $arrivalTime;
            }
            return $this->db->table("metro_arrivals")
                            ->join("metro_sub_routes", "MSR_id = MA_sub_route_id")
                            ->join("metro_routes", "MR_id = MSR_route_id")
                            ->select(
                                "MR_id AS route_id,
                                MR_name_TC AS route_name_TC,
                                MR_name_EN AS route_name_EN,
                                MSR_id AS sub_route_id,
                                MSR_name_TC AS sub_route_name_TC,
                                MSR_name_EN AS sub_route_name_EN,
                                MA_arrival_time AS departure_time"
                            )
                            ->where($condition)
                            ->whereIn("MA_sub_route_id", $subRoutes)
                            ->orderBy("MA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得總運行時間查詢類別
     * @param string $largerSeq 起站序號
     * @param string $smallerSeq 訖站序號
     * @param string $subRouteId 子路線代碼
     * @param int $direction 行駛方向
     * @param int $stopTime 停靠時間
     * @return mixed 查詢類別
     */
    function get_duration($largerSeq, $smallerSeq, $subRouteId, $direction, $stopTime)
    {
        try
        {
            $condition = [
                "MD_direction"      => $direction,
                "MSRS_sub_route_id" => $subRouteId
            ];
            return $this->db->table("metro_durations")
                            ->join(
                                "metro_sub_route_stations",
                                "MSRS_station_id = MD_station_id
                                AND MSRS_direction = MD_direction
                                AND MSRS_sub_route_id = MD_sub_route_id"
                            )
                            ->select(
                                "SUM(MD_duration) + SUM(MD_stop_time) - $stopTime AS duration"
                            )
                            ->where($condition)
                            ->where("MSRS_sequence BETWEEN $largerSeq AND $smallerSeq -1");
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
                "MD_sub_route_id" => $subRouteId,
                "MD_direction"    => $direction,
                "MD_station_id"   => $fromStationId
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
     * 取得指定捷運站的路線代碼查詢類別
     * @param string $stationId 捷運站代碼
     * @return mixed 查詢類別
     */
    function get_route_by_station($stationId)
    {
        try
        {
            $condition = [
                "MRS_station_id" => $stationId
            ];
            return $this->db->table("metro_route_stations")
                            ->select("MRS_route_id AS route_id")
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
            return $this->db->table("metro_sub_route_stations")
                            ->select("MSRS_sub_route_id AS sub_route_id")
                            ->where("MSRS_direction", $direction)
                            ->groupStart()
                                ->where("MSRS_station_id", $fromStationId)
                                ->orWhere("MSRS_station_id", $toStationId)
                            ->groupEnd()
                            ->groupBy("MSRS_sub_route_id")
                            ->having("COUNT(MSRS_sub_route_id) > 1");
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
    function get_sub_route_sequence($stationId, $subRouteId, $direction)
    {
        try
        {
            $condition = [
                "MSRS_station_id"   => $stationId,
                "MSRS_sub_route_id" => $subRouteId,
                "MSRS_direction"    => $direction
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
     * 取得捷運所有轉乘資料查詢類別
     * @return mixed 查詢類別
     */
    function get_transfers()
    {
        try
        {
            return $this->db->table("metro_transfers")
                            ->select(
                                "MT_from_station_id AS station_id,
                                MT_to_station_id AS transfer_station_id,
                                MT_transfer_time AS transfer_time"
                            )
                            ->groupBy("MT_from_station_id, MT_to_station_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
