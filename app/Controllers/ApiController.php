<?php

namespace App\Controllers;

use App\Models\BaseModel;
use App\Models\MetroModel;
use Exception;

class ApiController extends BaseController
{
    function __construct()
    {
        try
        {
            $this->baseModel  = new BaseModel();
            $this->metroModel = new MetroModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 通用：取得所有縣市資料（尚未開放）
     * @return array 縣市資料陣列
     */
    function get_cities()
    {
        try
        {
            return $this->send_response($this->baseModel->get_cities()->get()->getResult());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得所有捷運系統資料
     * 
     * 格式：/api/metro/system
     * @return array 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            return $this->send_response($this->metroModel->get_systems()->get()->getResult());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定捷運系統的所有路線
     * 
     * 格式：/api/metro/system/{MetroSystemId}
     * @param string $systemId 捷運系統
     * @return array 路線資料陣列
     */
    function get_metro_routes($systemId)
    {
        try
        {
            // 轉大寫
            $systemId = strtoupper($systemId);

            // 設定 GET 資料驗證格式
            $vData = [
                "systemId" => $systemId
            ];

            if (!$this->metro_validation($vData))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            // 查詢成功
            return $this->send_response($this->metroModel->get_routes($systemId)->get()->getResult());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定捷運系統及路線的所有車站
     * 
     * 格式：/api/metro/system/{MetroSystemId}/route/{MetroRouteId}
     * @param string $systemId 捷運系統代碼
     * @param string $routeId 路線代碼
     * @return array 車站資料陣列
     */
    function get_metro_stations($systemId, $routeId)
    {
        try
        {
            // 轉大寫
            $systemId = strtoupper($systemId);
            $routeId  = strtoupper($routeId);

            // 設定 GET 資料驗證格式
            $vData = [
                "systemId" => $systemId,
                "routeId"  => $routeId
            ];

            // 如果 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->metro_validation($vData))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }
            
            // 查詢成功
            return $this->send_response($this->metroModel->get_stations($systemId, $routeId)->get()->getResult());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定車站及終點車站的時刻表
     * 
     * 格式：/api/metro/arrival/from/{StationId}/to/{StationId}
     * @param string $fromStationId 車站代碼
     * @param string $toStationId 終點車站代碼（用於表示運行方向）
     * @return array 時刻表資料陣列
     */
    function get_metro_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 轉大寫
            $fromStationId = strtoupper($fromStationId);
            $toStationId   = strtoupper($toStationId);

            // 設定 GET 資料驗證格式
            $vData = [
                "stationId" => $fromStationId,
                "stationId" => $toStationId
            ];
            
            // 若 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->metro_validation($vData))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            // 取得起點站序號
            $fromStationSeq = $this->metroModel->get_station_sequence($fromStationId)->get()->getResult();
            // 取得目的站序號
            $toStationSeq   = $this->metroModel->get_station_sequence($toStationId)->get()->getResult();
            // 取得起點站與目的站都能到達的終點站
            $endStations    = $this->metroModel->get_end_stations($fromStationId, $toStationId)->get()->getResult();
            // 若查無起點站則回傳錯誤訊息      
            if (sizeof($fromStationSeq) == 0)
            {
                return $this->send_response(["notFound" => $fromStationId], 400, lang("Query.metroStationNotFound"));
            } 
            // 若查無目的站則回傳錯誤訊息      
            if (sizeof($toStationSeq) == 0)
            {
                return $this->send_response(["notFound" => $toStationId], 400, lang("Query.metroStationNotFound"));
            }
            // 若起點站序號大於目的站序號，則代表終點站為序號較小的一方。反之亦然
            if (intval($fromStationSeq[0]->MS_sequence) > intval($toStationSeq[0]->MS_sequence))
            {
                $endStationId = $endStations[0]->MA_end_station_id;
            }
            else
            {
                $endStationId = $endStations[sizeof($endStations) -1]->MA_end_station_id;
            }
            // 回傳資料
            $result = $this->metroModel->get_arrivals($fromStationId, $endStationId)->get()->getResult();
            // 若查無資料則代表起點站及目的站需跨線或跨支線，將回傳尚未開放訊息
            if (sizeof($result) == 0)
            {
                return $this->send_response([], 400, lang("Query.metroCrossBranchNotAvailable"));
            }
            // 查詢成功
            return $this->send_response($result);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 取得指定捷運系統、路線、經度及緯度的最近捷運車站
     * 
     * 格式：/api/metro/system/{MetroSystemId}/route/{MetroRouteId}/long/{Longitude}/lat/{Latitude}
     * @param string $systemId 捷運系統代碼
     * @param string $routeId 捷運路線代碼
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     */
    function get_metro_nearest_station($systemId, $routeId, $longitude, $latitude)
    {
        try
        {
            // 轉大寫
            $systemId = strtoupper($systemId);
            $routeId  = strtoupper($routeId);

            // 設定 GET 資料驗證格式
            $vData = [
                "systemId"  => $systemId,
                "routeId"   => $routeId,
                "longitude" => $longitude,
                "latitude"  => $latitude
            ];

            // 如果 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->metro_validation($vData))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            helper("getDistance");

            $longitude = floatval($longitude);
            $latitude  = floatval($latitude);

            // 取得指定捷運系統及路線的所有車站
            $stations = $this->metroModel->get_stations($systemId, $routeId)->get()->getResult();

            // 最近車站代碼
            $nearestStationIndex = -1;
            // 已知最短距離
            $shortestDistance = PHP_INT_MAX;

            for ($i = 0; $i < sizeof($stations); $i++)
            {
                // 取得兩座標直線距離
                $distance = get_distance($stations[$i]->MS_longitude, $stations[$i]->MS_latitude, $longitude, $latitude);
                // 若發現更近的車站則更新已知最短距離
                if ($distance >= $shortestDistance)
                {
                    continue;
                }
                $shortestDistance = $distance;
                $nearestStationIndex = $i;
            }
            
            // 查詢成功
            return $this->send_response($stations[$nearestStationIndex]);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 取得指定起點站及目的站之間的總運行時間
     * @param string $fromStationId 起點站代碼
     * @param string $toStationId 目的站代碼
     * @return int 總運行時間
     */
    function get_metro_durations($fromStationId, $toStationId)
    {
        try
        {
            // 轉大寫
            $fromStationId = strtoupper($fromStationId);
            $toStationId   = strtoupper($toStationId);
            // 驗證參數
            if (!$this->metro_validation_stations($fromStationId, $toStationId))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            // 取得捷運起點站及目的站序號
            $seq = $this->get_metro_sequences($fromStationId, $toStationId);
            // 若其一查無資料
            if (!$seq["hasResult"])
            {
                return $this->send_response(["notFound" => $seq["notFound"]], 400, lang("Query.metroStationNotFound"));
            }

            // 取得終點站
            $endStationId = $this->get_metro_end_station($fromStationId, $toStationId, $seq["from"], $seq["to"]);
            
            // 取得總運行時間
            $duration = $this->metroModel->get_durations($seq["from"], $seq["to"], $endStationId)->get()->getResult();
            
            // 查詢成功
            return $this->send_response($duration[0]);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }
}
