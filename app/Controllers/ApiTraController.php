<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use App\Models\TRAModel;
use Exception;

class ApiTraController extends ApiBaseController
{
    public $TRAModel;

    // 載入模型
    function __construct()
    {
        try
        {
            $this->TRAModel = new TRAModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
        }
    }

    /**
     * 取得所有臺鐵路線資料
     * @return array 路線資料
     */
    function get_tra_routes()
    {
        try
        {
            // 取得臺鐵所有路線
            $routes = $this->TRAModel->get_routes()->get()->getResult();

            // 重新排列資料
            $this->restructure_routes($routes);

            // 回傳資料
            return $this->send_response($routes);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定臺鐵路線的所有臺鐵站資料
     * @param string 路線代碼
     * @return array 臺鐵站資料
     */
    function get_tra_stations($routeId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, parent::TRA_ROUTE_ID_LENGTH))
            {
                return $this->send_response([], 400, (array) $this->validator->getErrors());
            }

            // 取得高鐵所有車站資料
            $stations = $this->TRAModel->get_stations($routeId)->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($stations);

            // 回傳資料
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定臺鐵路線及經緯度的最近臺鐵站資料
     * @param string 經度
     * @param string 緯度
     * @param int $limit 回傳數量
     * @return array 最近臺鐵站資料
     */
    function get_tra_nearest_station($longitude, $latitude, $limit = 1)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("Longitude", $longitude, parent::LONGLAT_LENGTH)
                || !$this->validate_param("Latitude", $latitude, parent::LONGLAT_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得臺鐵所有車站資料
            $station = $this->TRAModel->get_nearest_station($longitude, $latitude)->get($limit)->getResult();

            // 重新排列資料
            $this->restructure_stations($station);

            // 回傳資料
            return $this->send_response($station);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定臺鐵起訖站的時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 時刻表資料
     */
    function get_tra_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("FromStationId", $fromStationId, parent::TRA_STATION_ID_LENGTH)
                || !$this->validate_param("ToStationId", $toStationId, parent::TRA_STATION_ID_LENGTH))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得行駛方向（0：南下；1：北上）
            $direction = 0;
            if (intval(str_replace("TRA-", "", $fromStationId)) > intval(str_replace("TRA-", "", $toStationId)))
            {
                $direction = 1;
            }

            // 取得行經指定臺鐵起訖站的所有車次
            $trainIds = $this->TRAModel->get_trains_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();

            // 整理後的時刻表陣列
            $arrivals = [];

            // 透過列車代碼及起訖站來查詢時刻表
            for ($i = 0; $i < sizeof($trainIds); $i++)
            {
                $arrivalData = $this->TRAModel->get_arrivals($trainIds[$i]->RA_train_id, $fromStationId, $toStationId)->get()->getResult();
                
                if (sizeof($arrivalData) == 2)
                {
                    $arrivals[$i] = $arrivalData;
                }
            }

            // 若查無資料則回傳「查無此路線資料」
            if (!sizeof($arrivals))
            {
                return $this->send_response([], 200, lang("Query.dataNotAvailable"));
            }

            // 重新排序時刻表資料
            $this->restructure_arrivals_old($arrivals);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
