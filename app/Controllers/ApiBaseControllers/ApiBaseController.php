<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\BaseController;
use App\Models\BaseModel;
use Exception;

/**
 * API 底層控制器
 */
class ApiBaseController extends BaseController
{
    /**
     * 參數驗證失敗訊息
     */
    public $validateErrMsg = "";

    /**
     * 縣市代碼最大長度
     */
    const CITY_ID_LENGTH = 3;

    /**
     * 捷運代碼最大長度
     */
    const METRO_SYSTEM_ID_LENGTH = 4;

    /**
     * 公車站代碼最大長度
     */
    const BUS_STATION_ID_LENGTH = 17;

    /**
     * 捷運站代碼最大長度
     */
    const METRO_STATION_ID_LENGTH = 12;

    /**
     * 高鐵車站代碼最大長度
     */
    const THSR_STATION_ID_LENGTH = 11;

    /**
     * 臺鐵車站代碼最大長度
     */
    const TRA_STATION_ID_LENGTH = 11;

    /**
     * 經緯度代碼最大長度
     */
    const LONGLAT_LENGTH = 12;

    /**
     * 公車路線代碼最大長度
     */
    const BUS_ROUTE_ID_LENGTH = 17;

    /**
     * 捷運路線代碼最大長度
     */
    const METRO_ROUTE_ID_LENGTH = 12;

    /**
     * 捷運子路線代碼最大長度
     */
    const METRO_SUB_ROUTE_ID_LENGTH = 12;

    /**
     * 臺鐵路線代碼最大長度
     */
    const TRA_ROUTE_ID_LENGTH = 5;

    /**
     * 代碼安全長度
     */
    const SAFE_ID_LENGTH = 17;

    // 載入模型
    function __construct()
    {
        try
        {
            $this->baseModel = new BaseModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 驗證參數
     * @param string $name 參數名稱
     * @param string $param 參數值
     * @param int $length 參數長度限制
     * @return bool 驗證結果
     */
    function validate_data($name, $param, $length)
    {
        try
        {
            // 設定參數與驗證規則
            $data = [
                "$name" => $param
            ];
            $rules = [
                "$name" => "alpha_numeric_punct|max_length[$length]"
            ];
            // 若參數驗證失敗則回傳錯誤
            if (!$this->validateData($data, $rules))
            {
                $this->validateErrMsg = $this->validator->getError();
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 驗證參數
     * @param string $name 參數名稱
     * @param string &$param 參數
     * @param int $length 參數長度
     * @return bool 驗證結果
     */
    function validate_param($name, &$param, $length = self::SAFE_ID_LENGTH)
    {
        try
        {
            // 轉大寫
            $param = strtoupper($param);

            // 重置參數驗證失敗訊息
            $this->validateErrMsg = "";

            // 若參數驗證失敗則回傳錯誤
            if (!$this->validate_data($name, $param, $length))
            {
                $this->validateErrMsg = $this->validator->getError();
                return false;
            }

            // 若參數是經緯度且數值有異則回傳錯誤
            if ($this->is_coordniate($name) && !$this->is_valid_coordinate($param))
            {
                $this->validateErrMsg = lang("Validation.longLatInvalid", ["param" => $param]);
                return false;
            }

            // 回傳成功
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 檢查是否為座標名稱
     * @param string $name 名稱
     * @return bool 檢查結果
     */
    function is_coordniate($name)
    {
        try
        {
            if (!in_array($name, ["Longitude", "Latitude"]))
            {
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 檢查經緯度參數是否有異
     * @param string $coord 經緯度參數
     * @return bool 檢查結果
     */
    function is_valid_coordinate($coord)
    {
        try
        {
            if ($coord != floatval($coord))
            {
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列縣市資料
     * @param array &$cities 縣市陣列
     * @return void 不回傳值
     */
    function restructure_cities(&$cities)
    {
        try
        {
            foreach ($cities as $key => $value)
            {
                $cities[$key] = [
                    "CityId"   => $value->city_id,
                    "CityName" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ],
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列路線資料
     * @param array &$routes 路線資料
     * @return void 不回傳值
     */
    function restructure_routes(&$routes)
    {
        try
        {
            foreach ($routes as $key => $value)
            {
                $routes[$key] = [
                    "RouteId"   => $value->route_id,
                    "RouteName" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ],
                ];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列車站資料
     * @param array &$stations 車站資料
     * @param bool $isArray 是否為陣列
     * @return void 不回傳值
     */
    function restructure_stations(&$stations, $isArray = true)
    {
        try
        {
            $seq = 0;
            // 走遍車站陣列
            foreach ($stations as $key => $value)
            {
                $seq++;

                // 若查無序號則使用自動遞增的 $seq
                if (!isset($value->sequence))
                {
                    $value->sequence = $seq;
                }

                // 重新排列資料
                $stations[$key] = [
                    "StationId"   => $value->station_id,
                    "StationName" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ],
                    "StationLocation" => [
                        "CityId"   => $value->city_id,
                        "Longitude" => $value->longitude,
                        "Latitude"  => $value->latitude,
                    ],
                    "Sequence" => $value->sequence
                ];
            }

            // 若此資料非陣列則取第一筆資料
            if (!$isArray && sizeof($stations) > 0)
            {
                $stations = $stations[0];
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 比較 a 與 b 發車時間的大小
     * @param mixed $a
     * @param mixed $b
     */
    function cmpArrivals($a, $b)
    {
        try
        {
            // Spaceship Operator
            return $a["Schedule"]["DepartureTime"] <=> $b["Schedule"]["DepartureTime"];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @param array &$fromArrivals
     * @param array &$toArrivals
     * @return void 不回傳值
     */
    function restructure_bus_arrivals(&$arrivals, &$fromArrivals, &$toArrivals)
    {
        try
        {
            for ($i = 0; $i < sizeof($fromArrivals); $i++)
            {
                $arrivals[$i] = [
                    "Sequence" => $i + 1,
                    "Schedule" => [
                        "DepartureTime" => $fromArrivals[$i]->arrival_time,
                        "ArrivalTime"   => $toArrivals[$i]->arrival_time,
                    ]
                ];
            }
            usort($arrivals, [ApiBaseController::class, "cmpArrivals"]);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @return void 不回傳值
     */
    function restructure_arrivals(&$arrivals)
    {
        try
        {
            foreach ($arrivals as $key => $value)
            {
                $arrivals[$key] = [
                    "TrainId" => $value[0]->train_id,
                    "Schedule" => [
                        "DepartureTime" => $value[0]->arrival_time,
                        "ArrivalTime"   => $value[1]->arrival_time
                    ]
                ];
            }
            usort($arrivals, [ApiBaseController::class, "cmpArrivals"]);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 回傳抓到的例外
     * @param mixed &$e 例外資料
     * @return mixed 回傳資料
     */
    function get_caught_exception(&$e)
    {
        try
        {
            // 1 代表「查無資料」
            switch ($e->getCode())
            {
                case 1:
                    return $this->send_response([], 400, $e->getMessage());
                default:
                    log_message("critical", $e);
                    return $this->send_response([], 500, lang("Exception.exception"));
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, lang("Exception.exception"));
        }
    }
}
