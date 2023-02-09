<?php

namespace App\Controllers;

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

    public $baseModel;

    /**
     * 縣市代碼最大長度
     */
    const CITY_ID_LENGTH = 3;

    /**
     * 高鐵車次代碼最大長度
     */
    const THSR_TRAIN_ID_LENGTH = 4;

    /**
     * 高鐵車站代碼最大長度
     */
    const THSR_STATION_ID_LENGTH = 11;

    /**
     * 臺鐵車次代碼最大長度
     */
    const TRA_TRAIN_ID_LENGTH = 4;

    /**
     * 臺鐵車站代碼最大長度
     */
    const TRA_STATION_ID_LENGTH = 11;

    /**
     * 臺鐵路線代碼最大長度
     */
    const TRA_ROUTE_ID_LENGTH = 5;

    /**
     * 捷運代碼最大長度
     */
    const METRO_SYSTEM_ID_LENGTH = 4;

    /**
     * 捷運站代碼最大長度
     */
    const METRO_STATION_ID_LENGTH = 12;

    /**
     * 捷運路線代碼最大長度
     */
    const METRO_ROUTE_ID_LENGTH = 12;

    /**
     * 捷運子路線代碼最大長度
     */
    const METRO_SUB_ROUTE_ID_LENGTH = 12;

    /**
     * 公車站代碼最大長度
     */
    const BUS_STATION_ID_LENGTH = 17;

    /**
     * 公車路線代碼最大長度
     */
    const BUS_ROUTE_ID_LENGTH = 17;

    /**
     * 經緯度代碼最大長度
     */
    const LONGLAT_LENGTH = 12;

    /**
     * 代碼安全長度
     */
    const SAFE_ID_LENGTH = 17;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->baseModel = new BaseModel();
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            return $this->send_response([], 500, "Exception error");
        }
    }

    /**
     * 驗證參數
     * @param string $name 參數名稱
     * @param string $param 參數值
     * @param int $length 參數最大長度
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
            return in_array($name, ["Longitude", "Latitude"]);
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
     * 比較 a 與 b 發車時間的大小
     * @param mixed $a
     * @param mixed $b
     */
    function cmpArrivals($a, $b)
    {
        try
        {
            return $a["Schedule"]["DepartureTime"] <=> $b["Schedule"]["DepartureTime"];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 紀錄使用者存取 API 時參數驗證失敗的資訊
     */
    function log_validate_fail()
    {
        try
        {
            log_message("notice", "{$_SERVER['REQUEST_URI']} 驗證失敗。IP: {$_SERVER['REMOTE_ADDR']}");
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            throw $e;
        }
    }

    /**
     * 記錄使用者存取 API 成功的資訊
     */
    function log_access_success()
    {
        try
        {
            log_message("notice", "{$_SERVER['REQUEST_URI']} 存取成功。IP: {$_SERVER['REMOTE_ADDR']}");
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            throw $e;
        }
    }

    /**
     * 記錄使用者存取 API 失敗的資訊
     */
    function log_access_fail()
    {
        try
        {
            log_message("notice", "{$_SERVER['REQUEST_URI']} 存取失敗。IP: {$_SERVER['REMOTE_ADDR']}");
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            throw $e;
        }
    }
}
