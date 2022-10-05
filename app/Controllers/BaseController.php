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
                // 個別取得參數名稱、參數及限制長度
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
     * 驗證縣市參數
     * @param string &$cityId 縣市代碼
     * @return bool 驗證結果
     */
    function validate_city(&$cityId)
    {
        try
        {
            // 轉大寫
            $cityId = strtoupper($cityId);

            // 設定驗證預設值
            $paramData = [
                "cityId" => [$cityId, 3]
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
     * 驗證系統及路線
     * @param string $routeId 路線代碼
     * @param int $routeLength 路線長度限制
     * @return bool 驗證結果
     */
    function validate_route(&$routeId, $routeLength = 12)
    {
        try
        {
            // 轉大寫
            $routeId = strtoupper($routeId);

            // 設定驗證預設值
            $paramData = [
                "routeId" => [$routeId, $routeLength]
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
     * 驗證系統及路線
     * @param string &$systemId 系統代碼
     * @param string &$routeId 路線代碼
     * @param int $systemLength 系統長度限制
     * @param int $routeLength 路線長度限制
     * @return bool 驗證結果
     */
    function validate_system(&$systemId, $systemLength = 12)
    {
        try
        {
            // 轉大寫
            $systemId = strtoupper($systemId);

            // 設定驗證預設值
            $paramData = [
                "systemId" => [$systemId, $systemLength]
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
     * 取得捷運起點站及目的站序號
     * @param string $fromStationId 起點站代碼
     * @param string $toStationId 目的站代碼
     * @return array true（0）及兩站序號（1、2）
     * @return array false（0）及查無資料的捷運站代碼（1）
     */
    function get_metro_sequences($fromStationId, $toStationId)
    {
        try
        {
            // 取得起點站序號
            if (!$fromStationSeq = $this->metroModel->get_station_sequence($fromStationId)->get()->getResult())
            {
                // 回傳錯誤及起點站代碼
                return [
                    "hasResult" => false,
                    "notFound"  => $fromStationId
                ];
            }
            // 將起點站序號轉為數字
            $fromStationSeq = intval($fromStationSeq[0]->MS_sequence);

            // 取得目的站序號  
            if (!$toStationSeq = $this->metroModel->get_station_sequence($toStationId)->get()->getResult())
            {
                // 回傳錯誤及目的站代碼
                return [
                    "hasResult" => false,
                    "notFound"  => $toStationId
                ];
            }
            // 將目的站序號轉為數字
            $toStationSeq = intval($toStationSeq[0]->MS_sequence);

            // 回傳成功及兩站序號
            return [
                "hasResult" => true,
                "from" => $fromStationSeq,
                "to"   =>$toStationSeq
            ];
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
     * @return int 若查無終點站則回傳 -1
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
                return -1;
            }

            // 若起點站序號大於目的站序號，則代表終點站為序號較小的一方。反之亦然
            if ($seq1 > $seq2)
            {
                return $endStations[0]->MA_end_station_id;
            }
            return $endStations[sizeof($endStations) -1]->MA_end_station_id;
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
     * 重新排列時刻表資料
     * @param array &$arrivals 時刻表陣列
     * @param array &$fromArrivals
     * @param array &$toArrivals
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
            // 以 from_station_id 為 $arrivals 由小到大排序
            usort($arrivals, function ($a, $b) 
            {
                // Spaceship Operator
                return $a["arrivals"]["from"] <=> $b["arrivals"]["from"];
            });
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
            // 以 from_station_id 為 $arrivals 由小到大排序
            usort($arrivals, function ($a, $b) 
            {
                // Spaceship Operator
                return $a["arrivals"]["from"] <=> $b["arrivals"]["from"];
            });
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
