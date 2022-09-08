<?php

namespace App\Models;

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
                            ->select("HS_id, HS_name_TC, HS_name_EN, HS_city_id, HS_longitude, HS_latitude")
                            ->orderBy("HS_id");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }

    /**
     * 取得高鐵指定起訖站時刻表查詢類別
     * 
     * *列車需於當日行經起訖站並以訖站升序排列
     * @param string $fromStationId 起代碼
     * @param string $toStringId 訖站代碼
     * @return mixed 查詢類別
     */
    function get_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 取得於當日行經起訖站的列車
            // return $this->db->table("THSR_arrivals")
            //                 ->join("THSR_trains", "HT_id = HA_train_id")
            //                 ->select("HA_train_id, HA_station_id, HA_arrival_time")
            //                 ->where("DATE(HT_departure_date) = CURDATE()")
            //                 ->where("HA_station_id", $fromStationId)
            //                 ->orWhere("HA_station_id", $toStationId)
            //                 ->groupBy("HA_train_id")
            //                 ->having("COUNT(HA_train_id) >= 2");
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
                            ->select("HS_id,
                                      HS_name_TC,
                                      HS_name_EN,
                                      HS_city_id,
                                      HS_longitude,
                                      HS_latitude,
                                      FLOOR( SQRT( POWER( ABS( HS_longitude - $longitude ), 2 ) + POWER( ABS( HS_latitude - $latitude ), 2 ) ) * 11100 ) / 100 AS HS_distance")
                            ->orderBy("HS_distance")
                            ->limit(1);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}