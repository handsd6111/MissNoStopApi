<?php

namespace App\Controllers;

use App\Models\BaseModel;
use App\Models\FakeModel;
use App\Models\MetroModel;
use Exception;

class ApiController extends BaseController
{
    function __construct()
    {
        // $this->baseModel = new BaseModel();
        // $this->metroModel = new MetroModel();
        $this->fakeModel = new FakeModel();
    }

    /**
     * 通用：取得所有縣市資料
     * @return array 縣市資料陣列
     */
    function get_cities()
    {
        try
        {
            return $this->send_response($this->baseModel->get_cities()->get());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得所有捷運系統資料
     * @return array 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            return $this->send_response($this->fakeModel->get_systems());
            // return $this->send_response($this->metroModel->get_metro_systems()->get());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定捷運系統的所有路線
     * @param string $systemId 捷運系統
     * @return array 路線資料陣列
     */
    function get_metro_routes($systemId)
    {
        try
        {
            // 設定 GET 資料驗證格式
            $vData = [
                "systemId" => $systemId
            ];
            $vRules = [
                "systemId" => "alpha|max_length[4]"
            ];

            // 如果 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->validateData($vData, $vRules))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            // 查詢成功
            return $this->send_response($this->fakeModel->get_routes($systemId));
            // return $this->send_response($this->metroModel->get_routes($cityId)->get());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定捷運系統及路線的所有車站
     * @param string $systemId 捷運系統代碼
     * @param string $routeId 路線代碼
     * @return array 車站資料陣列
     */
    function get_metro_stations($systemId, $routeId)
    {
        try
        {
            // 設定 GET 資料驗證格式
            $vData = [
                "systemId" => $systemId,
                "routeId"  => $routeId
            ];
            $vRules = [
                "systemId" => "alpha|max_length[4]",
                "routeId"  => "max_length[12]"
            ];

            // 如果 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->validateData($vData, $vRules))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }
            
            // 查詢成功
            return $this->send_response($this->fakeModel->get_stations($systemId, $routeId));
            // return $this->send_response($this->metroModel->get_stations($systemId, $routeId)->get());
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 捷運：取得指定車站及終點車站的時刻表
     * @param string $stationId 車站代碼
     * @param string $endStationId 終點車站代碼（用於表示運行方向）
     * @return array 時刻表資料陣列
     */
    function get_metro_arrivals($stationId, $endStationId)
    {
        try
        {
            // 設定 GET 資料驗證格式
            $vData = [
                "stationId"    => $stationId,
                "endStationId" => $endStationId
            ];
            $vRules = [
                "stationId"    => "alpha_numeric_punct|max_length[12]",
                "endStationId" => "alpha_numeric_punct|max_length[12]"
            ];
            
            // 如果 GET 資料驗證失敗則回傳錯誤訊息
            if (!$this->validateData($vData, $vRules))
            {
                return $this->send_response((array) $this->validator->getErrors(), 400, lang("Validation.validation_error"));
            }

            $response = $this->fakeModel->get_arrivals($stationId, $endStationId);
            // $response = $this->metroModel->get_arrivals($stationId, $endStationId)->get();

            // 取得當前時間
            $nowTime   = explode(":", date("H:i"));
            $nowMinute = intval($nowTime[0]) * 60 + intval($nowTime[1]);

            // 將剩餘時間寫入回傳資料陣列
            for ($i = 0; $i < sizeof($response); $i++)
            {
                $arrivalTime   = explode(":", $response[$i]["MA_arrival_time"]);
                $arrivalMinute = intval($arrivalTime[0]) * 60 + intval($arrivalTime[1]);
                $response[$i]["MA_remain_time"] = $arrivalMinute - $nowMinute;
            }

            // 查詢成功
            return $this->send_response($response);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }
}
