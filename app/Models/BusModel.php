<?php

namespace App\Models;

use Exception;

/**
 * 公車模型（使用 query builder）
 */
class BusModel extends BaseModel
{
    /**
     * 查詢指定公車站代碼是否重複查詢類別
     * @param string $stationId 公車站代碼
     * @return mixed 查詢類別
     */
    function check_duplicated_station($stationId)
    {
        try
        {
            $condition = [
                "BDS_duplicated_id" => $stationId
            ];
            return $this->db->table("bus_duplicated_stations")
                            ->select("BDS_station_id AS station_id")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 查詢指定公車路線及序號的車站代碼查詢類別
     * @param string $routeId 公車路線
     * @param string $sequence 序號
     * @return mixed 查詢類別
     */
    function check_duplicated_sequence($routeId, $sequence)
    {
        try
        {
            $condition = [
                "BRS_route_id" => $routeId,
                "BRS_sequence"   => $sequence
            ];
            return $this->db->table("bus_route_stations")
                            ->select("BRS_station_id AS station_id")
                            ->where($condition);
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

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
                "BR_city_id" => $cityId
            ];
            return $this->db->table("bus_routes")
                            ->select("BR_id AS route_id,
                                      BR_name_TC AS name_TC,
                                      BR_name_EN AS name_EN")
                            ->where($condition)
                            ->orderBy("BR_id");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得指定公車路線的所有車站查詢類別
     * @param string $routeId 路線代碼
     * @return mixed 查詢類別
     */
    function get_stations($routeId)
    {
        try
        {
            $condition = [
                "BR_id" => $routeId
            ];
            return $this->db->table("bus_stations")
                            ->join("bus_route_stations", "BRS_station_id = BS_id")
                            ->join("bus_routes", "BR_id = BRS_route_id")
                            ->select("BS_id AS station_id,
                                      BS_name_TC AS name_TC,
                                      BS_name_EN AS name_EN,
                                      BR_city_id AS city_id,
                                      BS_longitude AS longitude,
                                      BS_latitude AS latitude
                                      BRS_sequence")
                            ->where($condition)
                            ->orderBy("BRS_sequence");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得指定公車路線及經緯度的最近車站查詢類別
     * @param string $routeId 路線代碼
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @return mixed 查詢類別
     */
    function get_nearest_station($routeId, $longitude, $latitude)
    {
        try
        {
            $condition = [
                "BR_id" => $routeId
            ];
            return $this->db->table("bus_stations")
                            ->join("bus_route_stations", "BRS_station_id = BS_id")
                            ->join("bus_routes", "BR_id = BRS_route_id")
                            ->select("BS_id AS station_id,
                                      BS_name_TC AS name_TC,
                                      BS_name_EN AS name_EN,
                                      BR_city_id AS city_id,
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
                                      ) / 100 AS BS_distance")
                            ->where($condition)
                            ->orderBy("BS_distance");
        }
        catch (Exception $e)
        {
            return $e;
        }
    }

    /**
     * 取得行經指定公車起訖站的所有車次查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @param int $direction 行車方向（0：南下；1：北上）
     * @return mixed 查詢類別
     */
    function get_bus_by_stations($fromStationId, $toStationId, $direction)
    {
        try
        {
            $condition1 = [
                "BA_station_id" => $fromStationId,
                "BA_direction"  => $direction
            ];
            $condition2 = [
                "BA_station_id" => $toStationId,
                "BA_direction"  => $direction
            ];
            return $this->db->table("bus_arrivals")
                            ->join("bus_cars", "BC_id = BA_car_id")
                            ->select("BA_car_id")
                            ->where($condition1)
                            ->orWhere($condition2)
                            ->groupBy("BA_car_id")
                            ->having("COUNT(BA_car_id) > 1");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得指定公車起訖站的時刻表查詢類別
     * @param string $busId 公車代碼
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($busId, $fromStationId, $toStationId)
    {
        try
        {
            $stations = [
                $fromStationId,
                $toStationId
            ];
            return $this->db->table("bus_arrivals")
                            ->select("BA_train_id AS train_id,
                                      BA_station_id AS station_id,
                                      BA_arrival_time AS arrival_time")
                            ->where("BA_car_id", $busId)
                            ->whereIn("BA_station_id", $stations)
                            ->orderBy("BA_arrival_time");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }
}
