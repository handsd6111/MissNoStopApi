<?php

namespace App\Controllers;

use App\Models\BaseModel;
use App\Models\BusModel;
use App\Models\MetroModel;
use App\Models\THSRModel;
use App\Models\TRAModel;
use Exception;

class ApiController extends BaseController
{
    function __construct()
    {
        try
        {
            $this->baseModel  = new BaseModel();
            $this->metroModel = new MetroModel();
            $this->THSRModel  = new THSRModel();
            $this->TRAModel   = new TRAModel();
            $this->busModel   = new BusModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得所有縣市資料
     * @return array 縣市資料陣列
     */
    function get_cities()
    {
        try
        {
            // 取得縣市
            $cities = $this->baseModel->get_cities()->get()->getResult();

            // 回傳資料
            return $this->send_response($cities);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得所有捷運系統資料
     * 
     * 格式：/api/metro/system
     * @return array 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            // 取得捷運系統
            $systems = $this->metroModel->get_systems()->get()->getResult();

            // 回傳資料
            return $this->send_response($systems);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定捷運系統的所有路線
     * 
     * 格式：/api/metro/system/{MetroSystemId}
     * @param string $systemId 捷運系統
     * @return array 路線資料陣列
     */
    function get_metro_routes($systemId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_system($systemId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            //取得路線
            $routes = $this->metroModel->get_routes($systemId)->get()->getResult();

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
     * 取得指定捷運系統及路線的所有車站
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
            // 驗證參數
            if (!$this->validate_system($systemId) || !$this->validate_route($routeId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得捷運站
            $stations = $this->metroModel->get_stations($systemId, $routeId)->get()->getResult();
            
            // 查詢成功
            return $this->send_response($stations);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定捷運起訖站的時刻表
     * 
     * 格式：/api/metro/arrival/from/{StationId}/to/{StationId}
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼（用於表示運行方向）
     * @return array 時刻表資料陣列
     */
    function get_metro_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得捷運起點站及目的站序號
            $seq = $this->get_metro_sequences($fromStationId, $toStationId);

            // 若其一查無資料
            if (!$seq["hasResult"])
            {
                return $this->send_response([], 200, lang("Query.metroStationNotFound", $seq["notFound"]));
            }

            // 取得終點站
            $endStationId = $this->get_metro_end_station($fromStationId, $toStationId, $seq["from"], $seq["to"]);

            // 若查無終點站則回傳路線未開放
            if ($endStationId == -1)
            {
                return $this->send_response([], 200, lang("Query.dataNotAvailable"));
            }

            // 取得時刻表
            $arrivals = $this->metroModel->get_arrivals($fromStationId, $endStationId)->get()->getResult();

            // 若查無資料則代表起點站及目的站需跨線或跨支線，將回傳尚未開放訊息
            if (!sizeof($arrivals))
            {
                return $this->send_response([], 200, lang("Query.metroCrossBranchNotAvailable"));
            }

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定捷運系統、路線、經度及緯度的最近捷運站
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
            // 驗證參數
            if (!$this->validate_system($systemId) || !$this->validate_route($routeId) || !$this->validate_coordinates($longitude, $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得最近捷運站
            $nearestStationData = $this->metroModel->get_nearest_station($systemId, $routeId, $longitude, $latitude)->get()->getResult();

            // 回傳資料
            return $this->send_response($nearestStationData);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定起點站及目的站之間的總運行時間
     * 
     * 格式：/api/metro/duration/from/{StationId}/to/{StationId}
     * @param string $fromStationId 起點站代碼
     * @param string $toStationId 目的站代碼
     * @return int 總運行時間
     */
    function get_metro_durations($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得捷運起訖站序號
            $seq = $this->get_metro_sequences($fromStationId, $toStationId);

            // 若其一查無資料
            if (!$seq["hasResult"])
            {
                return $this->send_response([], 200, lang("Query.metroStationNotFound", $seq["notFound"]));
            }

            // 取得終點站
            $endStationId = $this->get_metro_end_station($fromStationId, $toStationId, $seq["from"], $seq["to"]);
            
            // 若查無終點站則回傳路線未開放
            if ($endStationId == -1)
            {
                return $this->send_response([], 200, lang("Query.dataNotAvailable"));
            }

            // 取得總運行時間
            $duration = $this->metroModel->get_durations($seq["from"], $seq["to"], $endStationId)->get()->getResult()[0];
            
            // 回傳資料
            return $this->send_response($duration);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得高鐵所有車站資料
     * 
     * 格式：/api/THSR/station
     * @return array 高鐵站資料陣列
     */
    function get_thsr_stations()
    {
        try
        {
            // 取得高鐵所有車站資料
            $stations = $this->THSRModel->get_stations()->get()->getResult();

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
     * 取得高鐵指定起訖站時刻表資料
     * 
     * 格式：/api/THSR/arrival/from/{StationId}/to/{StationId}
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 起訖站時刻表資料
     */
    function get_thsr_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId, 11, 11))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得行駛方向（0：南下；1：北上）
            $direction = 0;
            if (intval(str_replace("THSR-", "", $fromStationId)) > intval(str_replace("THSR-", "", $toStationId)))
            {
                $direction = 1;
            }

            // 取得指定高鐵行經起訖站的所有車次
            $trainIds = $this->THSRModel->get_trains_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();

            // 整理後的時刻表陣列
            $arrivals = [];

            // 透過列車代碼及起訖站來查詢時刻表
            for ($i = 0; $i < sizeof($trainIds); $i++)
            {
                $arrivalData = $this->THSRModel->get_arrivals($trainIds[$i]->HA_train_id, $fromStationId, $toStationId)->get()->getResult();
                
                if (sizeof($arrivalData) == 2)
                {
                    $arrivals[$i] = $arrivalData;
                }
            }

            // 重新排列時刻表資料
            $this->restructure_arrivals($arrivals);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得高鐵指定經緯度最近車站
     * 
     * 格式：/api/THSR/station/long/{Longitude}/lat/{Latitude}
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     * @return array 最近高鐵站資料陣列
     */
    function get_thsr_nearest_station($longitude, $latitude)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_coordinates($longitude, $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得高鐵所有車站資料
            $station = $this->THSRModel->get_nearest_station($longitude, $latitude)->get()->getResult();

            // 重新排列資料
            $this->restructure_stations($station. false);

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
     * 取得所有臺鐵路線資料
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
     */
    function get_tra_stations($routeId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_route($routeId, 5))
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
     */
    function get_tra_nearest_station($routeId, $longitude, $latitude)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_coordinates($longitude, $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得臺鐵所有車站資料
            $station = $this->TRAModel->get_nearest_station($routeId, $longitude, $latitude)->get()->getResult()[0];

            // 重新排列資料
            $this->restructure_stations($station, false);

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
     */
    function get_tra_arrivals($fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId, 11, 11))
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
            $this->restructure_arrivals($arrivals);

            // 回傳資料
            return $this->send_response($arrivals);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定公車縣市的所有路線資料
     * @param string $cityId 縣市代碼
     * @return array 公車路線資料
     */
    function get_bus_routes($cityId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_city($cityId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車縣市的所有路線資料
            $routes = $this->busModel->get_routes($cityId)->get()->getResult();

            // 重新排列公車路線資料
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
     * 取得指定公車路線的所有車站資料
     * @param string $routeId 路線代碼
     * @return array 公車站資料
     */
    function get_bus_stations($routeId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_route($routeId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車路線的所有車站資料
            $stations = $this->busModel->get_stations($routeId)->get()->getResult();

            // 重新排列公車站資料
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
     * 取得指定公車路線及經緯度的最近車站資料
     * @param string $routeId 路線代碼
     * @param string $longitude 經度
     * @param string $latitude 緯度
     * @return array 公車站資料
     */
    function get_bus_nearest_station($routeId, $longitude, $latitude)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_route($routeId) || !$this->validate_coordinates($longitude, $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車路線及經緯度的最近車站資料
            $station = $this->busModel->get_nearest_station($routeId, $longitude, $latitude)->get()->getResult();

            // 重新排列公車站資料
            $this->restructure_stations($station, false);

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
     * 取得指定公車起訖站的時刻表資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 時刻表資料
     */
    function get_bus_arrivals($fromStationId, $toStationId)
    {
        try
        {
            
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得行駛方向（0：南下；1：北上）
            $direction = 0;
            if (intval(str_replace("bus-", "", $fromStationId)) > intval(str_replace("bus-", "", $toStationId)))
            {
                $direction = 1;
            }

            // 取得行經指定公車起訖站的所有車次
            $busIds = $this->busModel->get_bus_by_stations($fromStationId, $toStationId, $direction)->get()->getResult();

            // 整理後的時刻表陣列
            $arrivals = [];

            // 透過公車代碼及起訖站來查詢時刻表
            for ($i = 0; $i < sizeof($busIds); $i++)
            {
                $arrivalData = $this->busModel->get_arrivals($busIds[$i]->BC_id, $fromStationId, $toStationId)->get()->getResult();
                
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
            $this->restructure_arrivals($arrivals);

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
