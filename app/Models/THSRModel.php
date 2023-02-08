<?php

namespace App\Models;

use App\Models\BaseModel;
use Exception;

class THSRModel extends BaseModel
{
    function get_thsr_cities()
    {
        try
        {
            return $this->db->table("THSR_stations")
                            ->join("cities", "C_id = HS_city_id")
                            ->select("C_id as id,
                                    C_name_TC as name_TC,
                                    c_name_EN as name_EN")
                            ->groupBy("C_id");
        }
        catch (Exception $e)
        {
        }
    }

    /**
     * 取得高鐵所有車站查詢類別
     * @return mixed 查詢類別
     */
    function get_stations()
    {
        try
        {
            return $this->db->table("THSR_stations")
                            ->select("HS_id AS station_id,
                                      HS_name_TC AS name_TC,
                                      HS_name_EN AS name_EN,
                                      HS_city_id AS city_id,
                                      HS_longitude AS longitude,
                                      HS_latitude AS latitude")
                            ->orderBy("HS_id");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定高鐵行經起訖站的所有車次
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
                "HA_station_id"  => $fromStationId,
                "HA_direction" => $direction
            ];
            $condition2 = [
                "HA_station_id"  => $toStationId,
                "HA_direction" => $direction
            ];
            return $this->db->table("THSR_arrivals")
                            ->select("HA_train_id")
                            ->where($condition1)
                            ->orWhere($condition2)
                            ->groupBy("HA_train_id")
                            ->having("COUNT(HA_train_id) > 1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_arrivals_search($stationId)
    {
        try
        {
            return $this->db->table("THSR_arrivals")
                            ->select(
                                "HA_train_id as train_id,
                                HA_station_id as station_id,
                                HA_arrival_time as arrival_time,
                                HA_departure_time as departure_time"
                            );
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起訖站的高鐵時刻表查詢類別
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return mixed 高鐵時刻表查詢類別
     */
    function get_arrivals_by_stations($fromStationId, $toStationId)
    {
        try
        {
            $firstArrival = $this->db->table("THSR_arrivals")
                                     ->select(
                                        "HA_train_id,
                                        HA_station_id,
                                        HA_arrival_time,
                                        HA_departure_time"
                                     )
                                     ->where("HA_station_id", $fromStationId)
                                     ->orWhere("HA_station_id", $toStationId)
                                     ->groupBy("HA_train_id")
                                     ->having("COUNT(HA_train_id) > 1");
            return $this->db->newQuery()
                            ->fromSubquery($firstArrival, "first_arrival")
                            ->join("THSR_arrivals", "THSR_arrivals.HA_train_id = first_arrival.HA_train_id")
                            ->join("THSR_stations", "HS_id = THSR_arrivals.HA_station_id")
                            ->select(
                                "THSR_arrivals.HA_train_id as train_id,
                                THSR_arrivals.HA_station_id as station_id,
                                THSR_arrivals.HA_arrival_time as arrival_time,
                                THSR_arrivals.HA_departure_time as departure_time"
                            )
                            ->where("THSR_arrivals.HA_station_id", $fromStationId)
                            ->orWhere("THSR_arrivals.HA_station_id", $toStationId)
                            ->orderBy(
                                "THSR_arrivals.HA_train_id,
                                THSR_arrivals.HA_arrival_time"
                            );
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
            return $this->db->table("THSR_arrivals")
                            ->join("THSR_stations", "HS_id = HA_station_id")
                            ->select(
                                "HA_station_id as station_id,
                                HS_name_TC as station_name_TC,
                                HS_name_EN as station_name_EN,
                                HA_arrival_time as arrival_time,
                                HA_departure_time as departure_time"
                            )
                            ->where("HA_train_id", $trainId)
                            ->orderBy("HA_arrival_time");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得高鐵指定經緯度最近車站查詢類別
     * @param float $longitude 經度
     * @param float $latitude 緯度
     * @return mixed 查詢類別
     */
    function get_nearest_station($longitude, $latitude)
    {
        try
        {
            return $this->db->table("THSR_stations")
                            ->select("HS_id AS station_id,
                                      HS_name_TC AS name_TC,
                                      HS_name_EN AS name_EN,
                                      HS_city_id AS city_id,
                                      HS_longitude AS longitude,
                                      HS_latitude AS latitude,
                                      FLOOR(
                                        SQRT(
                                            POWER(
                                                ABS(
                                                    HS_longitude - $longitude
                                                ), 2
                                            ) +
                                            POWER(
                                                ABS(
                                                    HS_latitude - $latitude
                                                ), 2
                                            )
                                        ) * 11100
                                    ) / 100 AS HS_distance")
                            ->orderBy("HS_distance");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}