<?php

namespace App\Models;

use Exception;

/**
 * 公車模型（使用 query builder）
 */
class BusModel extends BaseModel
{
    /**
     * 取得指定公車縣市的所有路線查詢類別
     * @param string $cityId 縣市代碼
     * @return mixed 查詢類別
     */
    function get_routes($cityId)
    {
        try
        {
            $condition = [
                "BS_city_id" => $cityId
            ];
            return $this->db->table("bus_routes")
                            ->join("bus_route_stations", "BR_id = BRS_route_id")
                            ->join("bus_stations", "BRS_station_id = BS_id")
                            ->select(
                                "BR_id AS route_id,
                                BR_name_TC AS name_TC,
                                BR_name_EN AS name_EN"
                            )
                            ->where($condition)
                            ->groupBy("BR_name_TC")
                            ->orderBy("BR_id");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    function get_route_by_station($station1, $station2)
    {
        try
        {
            return $this->db->table("bus_route_stations")
                            ->select("BRS_route_id as route_id")
                            ->where("BRS_station_id", $station1)
                            ->orWhere("BRS_station_id", $station2)
                            ->groupBy("BRS_route_id")
                            ->having("COUNT(BRS_route_id) > 1");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得指定公車路線與行駛方向的所有車站查詢類別
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return mixed 查詢類別
     */
    function get_stations($routeId, $direction)
    {
        try
        {
            $condition = [
                "BR_id" => $routeId,
                "BRS_direction" => $direction
            ];
            return $this->db->table("bus_stations")
                            ->join("bus_route_stations", "BRS_station_id = BS_id")
                            ->join("bus_routes", "BR_id = BRS_route_id")
                            ->select(
                                "BS_id AS station_id,
                                BS_name_TC AS name_TC,
                                BS_name_EN AS name_EN,
                                BS_city_id AS city_id,
                                BS_longitude AS longitude,
                                BS_latitude AS latitude,
                                BRS_sequence AS sequence"
                            )
                            ->where($condition)
                            ->orderBy("BRS_sequence");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得指定公車經緯度的最近車站查詢類別
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @return mixed 查詢類別
     */
    function get_nearest_station($longitude, $latitude)
    {
        try
        {
            return $this->db->table("bus_stations")
                            ->join("bus_route_stations", "BRS_station_id = BS_id")
                            ->join("bus_routes", "BR_id = BRS_route_id")
                            ->select(
                                "BS_id AS station_id,
                                BS_name_TC AS name_TC,
                                BS_name_EN AS name_EN,
                                BS_city_id AS city_id,
                                BS_longitude AS longitude,
                                BS_latitude AS latitude,
                                FLOOR(
                                    SQRT(
                                        POWER(
                                            ABS(
                                                BS_longitude - $longitude
                                            ), 2
                                        ) +
                                        POWER(
                                            ABS(
                                                BS_latitude - $latitude
                                            ), 2
                                        )
                                    ) * 11100
                                ) / 100 AS BS_distance"
                            )
                            ->orderBy("BS_distance");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得指定公車路線及起訖站的序號查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @return mixed 查詢類別
     */
    function get_sequences($fromStationId, $toStationId)
    {
        try
        {
            return $this->db->table("bus_route_stations")
                            ->select("BRS_station_id, BRS_sequence")
                            ->where("BRS_station_id", $fromStationId)
                            ->orWhere("BRS_station_id", $toStationId);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定公車路線、行駛方向及起訖站的時刻表查詢類別
     * @param int $direction 行駛方向
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($direction, $fromStationId)
    {
        try
        {
            $condition = [
                "BA_station_id"    => $fromStationId,
                "BA_direction"    => $direction
            ];
            return $this->db->table("bus_arrivals")
                            ->select(
                                "BA_station_id AS station_id,
                                BA_arrival_time AS arrival_time"
                            )
                            ->where($condition)
                            ->orderBy("BA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_arrival_new($fromStationId, $toStationId, $direction)
    {
        try
        {
            return $this->db->table("bus_arrivals")
                            ->join("bus_stations", "BS_id = BA_station_id")
                            ->join("bus_route_stations", "BRS_station_id = BA_station_id")
                            ->join("bus_routes", "BR_id = BRS_route_id")
                            ->select(
                                "BR_id as route_id,
                                BR_name_TC as route_name_TC,
                                BR_name_EN as route_name_EN,
                                BS_id as station_id,
                                BS_name_TC as station_name_TC,
                                BS_name_EN as station_name_EN,
                                BA_direction as direction,
                                BA_arrival_time AS arrival_time"
                            )
                            ->groupStart()
                                ->where("BA_station_id", $fromStationId)
                                ->orWhere("BA_station_id", $toStationId)
                            ->groupEnd()
                            ->where("BA_direction", $direction)
                            ->orderBy("BA_direction, BA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
