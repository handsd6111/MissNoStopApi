<?php

namespace App\Models;

use App\Models\BaseModel;
use Exception;

class THSRModel extends BaseModel
{
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
            log_message("critical", $e->getMessage());
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
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得高鐵指定車次及起訖站的時刻表查詢類別
     * @param string $trainId 車次代碼
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
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
            return $this->db->table("THSR_arrivals")
                            ->select("HA_train_id AS train_id,
                                      HA_station_id AS station_id,
                                      HA_arrival_time AS arrival_time")
                            ->where("HA_train_id", $trainId)
                            ->whereIn("HA_station_id", $stations)
                            ->orderBy("HA_arrival_time");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
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
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}