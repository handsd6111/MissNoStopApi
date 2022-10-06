<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Psr\Log\LoggerInterface;
use App\Models\BaseModel;
use App\Models\MetroModel;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    
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
     * Constructor.
     */
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    protected $validateErrMsg = "";

    /**
     * 驗證底層
     * @param array $paramData 參數資料
     * @return bool 驗證結果
     */
    function validate_base($paramData)
    {
        try
        {
            $this->validateErrMsg = "";

            // 檢查每一份參數資料
            foreach ($paramData as $key => $value)
            {
                // 個別取得參數名稱、參數及參數長度
                $name   = $key;
                $param  = $value[0];
                $length = $value[1];

                // 設定參數與驗證規則
                $data = [
                    "$name" => $param,
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

                // 若參數非經緯度則繼續下一筆參數
                if ($name != "longitude" && $name != "latitude")
                {
                    continue;
                }

                // 若經緯度數值有異則回傳錯誤
                if ($param != floatval($param))
                {
                    $this->validateErrMsg = lang("Validation.longLatInvalid");
                    return false;
                }
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
     * 驗證參數
     * @param string $paramName 參數名稱
     * @param string &$param 參數
     * @param int $length 參數長度
     * @return bool 驗證結果
     */
    function validate_param($paramName, &$param, $length = 12)
    {
        try
        {
            // 轉大寫
            $param = strtoupper($param);

            // 設定驗證預設值
            $paramData = [
                "$paramName" => [$param, $length]
            ];

            // 回傳驗證結果
            return $this->validate_base($paramData);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 驗證經緯度參數
     * @param string &$longitude 經度字串
     * @param string &$latitude 緯度字串
     * @param int $longLength 經度長度限制
     * @param int $latLength 緯度長度限制
     * @return bool 驗證結果
     */
    function validate_coordinates(&$longitude, &$latitude, $longLength = 12, $latLength = 12)
    {
        try
        {
            // 設定驗證預設值
            $paramData = [
                "longitude" => [$longitude, $longLength],
                "latitude"  => [$latitude, $latLength]
            ];

            // 回傳驗證結果
            return $this->validate_base($paramData);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 驗證起訖站代碼
     * @param string &$fromStationId 起站代碼
     * @param string &$toStationId 訖站代碼
     * @param int $fromLength 起站長度限制
     * @param int $toLength 訖站長度限制
     * @return bool 驗證結果
     */
    function validate_stations(&$fromStationId, &$toStationId, $fromLength = 12, $toLength = 12)
    {
        try
        {
            // 轉大寫
            $fromStationId = strtoupper($fromStationId);
            $toStationId   = strtoupper($toStationId);

            // 設定驗證預設值
            $paramData = [
                "fromStationId" => [$fromStationId, $fromLength],
                "toStationId"   => [$toStationId, $toLength]
            ];

            // 回傳驗證結果
            return $this->validate_base($paramData);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定捷運起訖站的資料
     * @param string 起站代碼
     * @param string 訖站代碼
     * @return array 起訖站資料
     * @return bool 查詢失敗
     */
    function get_metro_station_data($fromStationId, $toStationId)
    {
        try
        {
            // 取得起訖站序號
            $sequences = $this->get_metro_sequences($fromStationId, $toStationId);

            if (!$sequences)
            {
                return false;
            }

            // 取得終點站
            $end_station_id = $this->get_metro_end_station($fromStationId, $toStationId, $sequences[0], $sequences[1]);

            if (!$end_station_id)
            {
                return false;
            }

            // 回傳資料
            $data = [
                "fromSeq" => $sequences[0],
                "toSeq" => $sequences[1],
                "endStationId" => $end_station_id
            ];
            return $data;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得捷運起訖站序號
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 起訖站序號
     * @return bool 查詢失敗
     */
    function get_metro_sequences($fromStationId, $toStationId)
    {
        try
        {
            // 取得起訖站序號
            $fromStationSeq = $this->metroModel->get_station_sequence($fromStationId)->get()->getResult();
            $toStationSeq = $this->metroModel->get_station_sequence($toStationId)->get()->getResult();

            // 若查無序號則回傳否
            if (!$fromStationSeq || !$toStationSeq)
            {
                return false;
            }

            // 將起訖站序號轉為數字
            $fromStationSeq = intval($fromStationSeq[0]->MS_sequence);
            $toStationSeq = intval($toStationSeq[0]->MS_sequence);

            // 回傳成功及兩站序號
            return [$fromStationSeq, $toStationSeq];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起點站、目的站及兩站序號的終點站代碼
     * @param string $id1 起點站代碼
     * @param string $id2 目的站代碼
     * @param string $seq1 起點站序號
     * @param string $seq2 目的站序號
     * @return string 終點站代碼
     * @return bool 查詢失敗
     */
    function get_metro_end_station($id1, $id2, $seq1, $seq2)
    {
        try
        {
            // 取得起點站與目的站都能到達的終點站
            $endStations = $this->metroModel->get_end_stations($id1, $id2)->get()->getResult();
            
            // 若查無終點站則回傳 -1
            if (sizeof($endStations) == 0)
            {
                return false;
            }

            // 若起點站序號大於目的站序號，則代表終點站為序號較小的一方。反之亦然
            if ($seq1 > $seq2)
            {
                return $endStations[0]->end_station_id;
            }
            return $endStations[sizeof($endStations) -1]->end_station_id;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新排列系統資料
     * @param array &$systems 系統資料
     * @return void 不回傳值
     */
    function restructure_systems(&$systems)
    {
        try
        {
            // 重新排列資料
            foreach ($systems as $key => $value)
            {
                $systems[$key] = [
                    "system_id"   => $value->system_id,
                    "system_name" => [
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
            // 重新排列資料
            foreach ($routes as $key => $value)
            {
                $routes[$key] = [
                    "route_id"   => $value->route_id,
                    "route_name" => [
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
                    "station_id"   => $value->station_id,
                    "station_name" => [
                        "TC" => $value->name_TC,
                        "EN" => $value->name_EN
                    ],
                    "station_location" => [
                        "city_id"   => $value->city_id,
                        "longitude" => $value->longitude,
                        "latitude"  => $value->latitude,
                    ],
                    "sequence" => $value->sequence
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
            return $a["arrivals"]["from"] <=> $b["arrivals"]["from"];
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
    function restructure_metro_arrivals(&$arrivals)
    {
        try
        {
            // 重新排列時刻表資料
            foreach ($arrivals as $key => $value)
            {
                $arrivals[$key] = [
                    "sequence" => $value->sequence,
                    "arrivals" => [
                        "from" => $value->departure_time,
                        "to"   => $value->arrival_time,
                    ]
                ];
            }
            usort($arrivals, [BaseController::class, "cmpArrivals"]);
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
            // 重新排列時刻表資料
            for ($i = 0; $i < sizeof($fromArrivals); $i++)
            {
                $arrivals[$i] = [
                    "arrivals" => [
                        "from" => $fromArrivals[$i]->arrival_time,
                        "to"   => $toArrivals[$i]->arrival_time,
                    ]
                ];
            }
            usort($arrivals, [BaseController::class, "cmpArrivals"]);
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
            /**
             * [
             *      {
             *          "train_id"     => 列車代碼,
             *          "station_id"   => 起站代碼,
             *          "arrival_time" => 到站時間
             *      },
             *      {
             *          "train_id"     => 列車代碼,
             *          "station_id"   => 訖站代碼,
             *          "arrival_time" => 到站時間
             *      }
             * ]
             */
            // 重新排列時刻表資料（有車次）
            foreach ($arrivals as $key => $value)
            {
                $arrivals[$key] = [
                    "train_id" => $value[0]->train_id,
                    "arrivals" => [
                        "from" => $value[0]->arrival_time,
                        "to"   => $value[1]->arrival_time
                    ]
                ];
            }
            usort($arrivals, [BaseController::class, "cmpArrivals"]);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 送出 response
     * @param array $data 回傳資料，預設為空
     * @param int $status 狀態碼，預設為 200（即「成功」）
     * @param string $message 回傳訊息，預設為「OK」
     * @param array $headers 標頭，預設為空
     * @return mixed 回傳資料
     */
    function send_response($data = [], $status = 200, $message = "OK", $headers = [])
    {
        $result = [
            "data" => $data,
            "status" => $status,
            "message" => $message
        ];
        
        $this->response->setJSON($result)->setStatusCode($status);

        return $this->response;
    }
}
