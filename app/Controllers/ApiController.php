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
     * 取得縣市資料
     * 
     * 格式：/api/General/City
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
     * 取得「捷運系統」資料
     * 
     * 格式：/api/Metro/System
     * @return array 捷運系統資料
     */
    function get_metro_systems()
    {
        try
        {
            // 取得捷運系統
            $systems = $this->metroModel->get_systems()->get()->getResult();

            // 重新排列資料
            $this->restructure_systems($systems);

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
     * 取得指定系統的「捷運路線」資料
     * 
     * 格式：/api/Metro/Route/{SystemId}
     * @param string $systemId 捷運系統
     * @return array 路線資料陣列
     */
    function get_metro_routes($systemId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("SystemId", $systemId, 4))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            //取得路線
            $routes = $this->metroModel->get_routes($systemId)->get()->getResult();

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
     * 取得指定路線的「捷運站」資料
     * 
     * 格式：/api/Metro/StationOfRoute/{RouteId}
     * @param string $routeId 路線代碼
     * @return array 車站資料陣列
     */
    function get_metro_stations($routeId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, 12))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得捷運站
            $stations = $this->metroModel->get_stations($routeId)->get()->getResult();

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
     * 取得指定路線及經緯度的「最近捷運站」資料
     * 
     * 格式：/api/Metro/NearestStation/{RouteId}/{Longitude}/{Latitude}
     * @param string $routeId 捷運路線代碼
     * @param float $longitude 經度（-180 ~ 180）
     * @param float $latitude 緯度（-90 ~ 90）
     * @param int $limit 回傳數量
     */
    function get_metro_nearest_station($routeId, $longitude, $latitude, $limit = 1)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, 12) || !$this->validate_coordinates($longitude, $latitude))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得最近捷運站
            $station = $this->metroModel->get_nearest_station($routeId, $longitude, $latitude, $limit)->get()->getResult();
            
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
     * 取得指定起訖站的「捷運時刻表」資料
     * 
     * 格式：/api/Metro/Arrival/{FromStationId}/{ToStationId}
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

            // 取得起訖站資料
            $data = $this->get_metro_station_data($fromStationId, $toStationId);

            // 若查無資料則回傳查無資料
            if (!$data)
            {
                return $this->send_response([], 400, lang("Query.resultNotFOund"));
            }

            // 取得總運行時間
            $duration = $this->metroModel->get_durations($data["fromSeq"], $data["toSeq"], $data["endStationId"])->get()->getResult()[0]->duration;

            // 取得時刻表
            $arrivals = $this->metroModel->get_arrivals($fromStationId, $data["endStationId"], $duration)->get()->getResult();

            // 重新排列資料
            $this->restructure_metro_arrivals($arrivals);

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
     * 取得指定起訖站的「捷運運行時間」資料
     * 
     * 格式：/api/Metro/Duration/{FromStationId}/{ToStationId}
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

            // 取得起訖站資料
            $data = $this->get_metro_station_data($fromStationId, $toStationId);

            // 若查無資料則回傳查無資料
            if (!$data)
            {
                return $this->send_response([], 400, lang("Query.resultNotFOund"));
            }

            // 取得總運行時間
            $duration = $this->metroModel->get_durations($data["fromSeq"], $data["toSeq"], $data["endStationId"])->get()->getResult()[0]->duration;
            
            // 回傳資料
            return $this->send_response(intval($duration));
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
            if (!$this->validate_param("RouteId", $routeId, 5))
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
            $station = $this->TRAModel->get_nearest_station($routeId, $longitude, $latitude)->get()->getResult();

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
            if (!$this->validate_param("CityId", $cityId, 3))
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
            return $this->send_response([$e], 500, lang("Exception.exception"));
        }
    }

    /**
     * 取得指定公車路線與行駛方向的所有車站資料
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @return array 公車站資料
     */
    function get_bus_stations($routeId, $direction)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_param("RouteId", $routeId, 17))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 取得指定公車路線的所有車站資料
            $stations = $this->busModel->get_stations($routeId, $direction)->get()->getResult();

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
            if (!$this->validate_param("RouteId", $routeId, 17) || !$this->validate_coordinates($longitude, $latitude))
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
     * 取得指定公車路線、行駛方向及起訖站的時刻表
     * @param string $routeId 路線代碼
     * @param int $direction 行駛方向
     * @param string $fromStationId 起站代碼
     * @param string $toStringId 訖站代碼
     * @return array 時刻表資料
     */
    function get_bus_arrivals($route, $direction, $fromStationId, $toStationId)
    {
        try
        {
            // 驗證參數
            if (!$this->validate_stations($fromStationId, $toStationId, 17, 17))
            {
                return $this->send_response([], 400, $this->validateErrMsg);
            }

            // 起站時刻表
            $fromArrivals = $this->busModel->get_arrivals($route, $direction, $fromStationId)->get()->getResult();

            // 起站時刻表
            $toArrivals = $this->busModel->get_arrivals($route, $direction, $toStationId)->get()->getResult();

            if (!sizeof($fromArrivals))
            {
                return $this->send_response(["stationId" => $fromStationId], 400, lang("Query.stationNotFound"));
            }
            if (!sizeof($toArrivals))
            {
                return $this->send_response(["stationId" => $toStationId], 400, lang("Query.stationNotFound"));
            }

            $arrivals = [];

            // 重新排序時刻表資料
            $this->restructure_bus_arrivals($arrivals, $fromArrivals, $toArrivals);

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
