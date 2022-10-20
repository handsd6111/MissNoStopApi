<?php

namespace App\Controllers\ApiBaseControllers;

use App\Controllers\ApiBaseControllers\ApiBaseController;
use App\Controllers\ApiBaseControllers\ApiMetroBaseController;
use App\Models\MetroModel;
use Exception;

/**
 * 路線規劃 API 底層控制器
 */
class ApiRoutePlanBaseController extends ApiBaseController
{
    protected $transportNames = [
        "BUS",
        "METRO",
        "THSR",
        "TRA"
    ];

    protected $transportName;
    protected $startStationId;
    protected $endStationId;
    protected $startRouteId;
    protected $endRouteId;
    protected $departureTime;

    /**
     * 拜訪車站佇列
     * $this->queue = [
     *      StationId: 車站代碼,
     * ];
     */
    private $queue;

    /**
     * @var array 轉乘車站原始資料
     * $this->transferRaw = [
     *      {
     *          from_station_id: 起站代碼,
     *          from_route_id: 起站路線代碼,
     *          to_station_id: 訖站代碼,
     *          transfer_time: 轉乘耗費時間（秒）
     *      },
     * ];
     */
    private $transferRaw;

    /**
     * @var array 轉乘車站鄰接矩陣
     * $this->transferAJ = [
     *      StationId: [         車站代碼（例：TRTC-R13）
     *          "TFStationId": 轉乘站代碼（例：TRTC-O11）,
     *          TFStationId: 轉乘耗費時間（秒）
     *      ],
     * ];
     */
    private $transferAJ;

    /**
     * @var array 轉乘路線車站
     * $this->transferRS = [
     *      RouteId: [     路線代碼
     *          StationId: 車站代碼,
     *      ],
     *      StationId: 路線代碼,
     * ];
     */
    private $transferRS;

    /**
     * @var array 同一條路線上的轉乘車站鄰接矩陣
     * $this->stationAJ = [
     *      StationId: [
     *          AdjacentStationId: 鄰接車站代碼,
     *      ],
     * ];
     */
    private $stationAJ;

    /**
     * 已拜訪車站（用於演算法）
     * $this->visited = [
     *      StationId: 是否已拜訪
     * ];
     */
    private $visited;

    /**
     * 車站源頭（用於演算法）
     * $this->src = [
     *      StationId: 源頭車站代碼
     * ]
     */
    private $src;

    /**
     * 時刻表
     * $this->arrivals = [
     *      {
     *          SubRouteId:    子路線代碼,
     *          FromStationId: 起站代碼,
     *          ToStationId:   訖站代碼,
     *          Schedule: [
     *              DepartureTime: 發車時間,
     *              ArrivalTime:   抵達時間,
     *              Duration:      耗費時間
     *          ]
     *      },
     * ];
     */
    private $arrivals;

    /**
     * 載入模型
     */
    function __construct()
    {
        try
        {
            $this->metroModel      = new MetroModel();
            $this->metroController = new ApiMetroBaseController();
        }
        catch (Exception $e)
        {
            return $this->get_caught_exception($e);
        }
    }

    /**
     * 驗證交通工具與車站參數
     * @param string &$transportName 交通工具名稱
     * @param string $stationName 車站參數名稱
     * @param string $stationId 車站參數
     * @return bool 驗證結果
     */
    function validate_transport_param(&$transportName, $stationName, $stationId)
    {
        try
        {
            $this->validateErrMsg = "";

            // 檢查交通工具名稱是否可辨認
            if (!in_array(strtoupper($transportName), $this->transportNames))
            {
                $this->validateErrMsg = lang("RoutePlan.transportNameNotFound", [$transportName]);
                return false;
            }

            // 轉大寫
            $transportName = strtoupper($transportName);

            // 取得車站代碼限制長度
            $stationIdLength = $this->get_station_id_length($transportName);

            // 驗證車站參數
            if (!$this->validate_param($stationName, $stationId, $stationIdLength))
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
     * 取得車站代碼限制長度
     * @param string $transportName 交通工具名稱
     * @return int 代碼限制長度
     */
    function get_station_id_length($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return parent::BUS_STATION_ID_LENGTH;
                    break;
                case "METRO":
                    return parent::METRO_STATION_ID_LENGTH;
                    break;
                case "THSR":
                    return parent::THSR_STATION_ID_LENGTH;
                    break;
                case "TRA":
                    return parent::TRA_STATION_ID_LENGTH;
                    break;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得路線規劃資料
     * @param string $transportName 運輸工具名稱
     * @param string $startStationId 起站代碼
     * @param string $endStationId 訖站代碼
     * @return array 路線規劃資料
     */
    function get_route_plan($transportName, $startStationId, $endStationId, $departureTime)
    {
        try
        {
            $this->transportName  = $transportName;
            $this->startStationId = $startStationId;
            $this->endStationId   = $endStationId;
            $this->departureTime  = $departureTime;

            try
            {
                $arrival = $this->get_arrival($transportName, $startStationId, $endStationId, $departureTime);

                if (!$arrival)
                {
                    return $this->get_cross_route_plan();
                }
                return $arrival;
            }
            catch (Exception $e)
            {
                return $this->get_cross_route_plan();
            }
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
            throw new Exception("指定起訖站或發車時間無法到達目的地", 400);
        }
    }

    function get_cross_route_plan()
    {
        try
        {
            // 初始化演算法資料
            $this->queue    = [$this->startStationId];         // 車站佇列
            $this->src      = ["{$this->startStationId}" => -1]; // 車站源頭
            $this->arvTimes = ["{$this->startStationId}" => $this->departureTime]; // 最早抵達時間
            $this->arrivals = []; // 時刻表紀錄
            $this->visited  = []; // 已造訪車站

            // 取得起訖站所屬路線
            $this->startRouteId = $this->get_route($this->transportName, $this->startStationId);
            $this->endRouteId   = $this->get_route($this->transportName, $this->endStationId);

            // 初始化轉乘資料
            $this->innitialize_algorithm_data();

            $safeLoops = 1000;

            while ($this->queue)
            {
                if (!--$safeLoops) throw new Exception("Infinite Loop Error", 500);

                // 取得佇列首項：源頭站
                $source = array_shift($this->queue);

                // 若已造訪此源頭站則跳過
                if (isset($this->visited[$source]))
                {
                    continue;
                }

                // 紀錄源頭站已造訪
                $this->visited[$source] = true;

                // 走訪源頭站所有鄰居站（源頭站與鄰居站同屬一路線）
                foreach ($this->stationAJ[$source] as $neighbor => $connected)
                {
                    // 若已造訪此鄰居站則跳過
                    if (isset($this->visited[$neighbor]))
                    {
                        continue;
                    }

                    // 將鄰居及其轉乘站（若有）推入佇列
                    $this->add_to_queue($neighbor);

                    // 取得源頭站發車時間
                    $departureTime = $this->get_arrival_time($source);

                    // 時刻表：源頭 -> 鄰居
                    $arrival = $this->get_arrival($this->transportName, $source, $neighbor, $departureTime);

                    // 若查無時刻表則跳過
                    if (!$arrival)
                    {
                        continue;
                    }

                    // 取得鄰居站新舊抵達時間
                    $arrivalTime = $arrival["Schedule"]["ArrivalTime"];
                    $oldArrivalTime = $this->get_arrival_time($neighbor);

                    // 若鄰居的抵達時間不是前所未有的早則跳過
                    if (isset($this->src[$neighbor]) && strcmp($arrivalTime, $oldArrivalTime) > 0)
                    {
                        continue;
                    }

                    // 更新鄰居其轉乘站（若有）的演算法資料
                    $this->set_arrival_time($neighbor, $arrivalTime);
                    $this->set_source($source, $neighbor);
                    $this->set_arrival($source, $neighbor, $arrival);
                }
            }

            // 修正起站源頭
            $this->src[$this->startStationId] = -1;

            // 修正訖站演算法資料
            $this->reroute_end_station_source($this->transportName, $this->endRouteId, $this->endStationId);

            // 從訖站一路至起站地逆向查詢時刻表資料
            $arrivals = $this->retrace_source($this->endStationId);

            // $this->restructure_arrivals($arrivals);

            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function restructure_arrivals(&$arrivals)
    {
        try
        {
            foreach ($arrivals as $i => $arrival)
            {
                $fromStationData = $this->metroModel->get_station($arrival["FromStationId"])->get()->getResult()[0];
                $toStationData   = $this->metroModel->get_station($arrival["ToStationId"])->get()->getResult()[0];
                $arrival["FromStation"] = $fromStationData;
                $arrival["ToStation"] = $toStationData;
                $arrivals[$i] = $arrival;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定車站的轉乘站代碼
     * @param string $stationId 車站代碼
     * @return string 轉乘站代碼
     * @return null 查無轉乘站
     */
    function get_transfer_station($stationId)
    {
        try
        {
            if (isset($this->transferAJ[$stationId]["TFStationId"]))
            {
                return $this->transferAJ[$stationId]["TFStationId"];
            }
            return null;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 初始化轉乘資料
     * @param string $transportName 交通工具名稱
     * @param string $startStationId 起站代碼
     * @param string $endStationId 訖站代碼
     * @return void 不回傳值
     */
    function innitialize_algorithm_data()
    {
        try
        {
            $this->transferRaw = $this->get_transfers_raw($this->transportName);
            $this->transferRS  = $this->get_transfer_route_station();
            $this->transferAJ  = $this->get_transfer_adjacency();
            $this->stationAJ   = $this->get_station_adjacencies($this->transportName, $this->startStationId, $this->endStationId);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 逆向查詢源頭並取得時刻表資料
     * @param string $endStationId 訖站代碼
     * @return array 時刻表資料
     */
    function retrace_source($endStationId)
    {
        try
        {
            $arrivals  = [];
            $stationId = $endStationId;

            while ($this->src[$stationId] != -1)
            {
                $source = $this->src[$stationId];
                array_unshift($arrivals, $this->arrivals[$source][$stationId]);
                $stationId = $source;
            }

            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 重新規劃訖站的源頭
     * @param string $transportName 交通工具名稱
     * @param string $endRouteId 訖站路線代碼
     * @param string $endStationId 訖站代碼
     * @return void 不回傳值
     */
    function reroute_end_station_source($transportName, $endRouteId, $endStationId)
    {
        try
        {
            if (!isset($this->src[$endStationId]) || !isset($this->src[$this->src[$endStationId]]))
            {
                return;
            }

            // 訖站源頭站的源頭站
            $endGrandparent = $this->src[$this->src[$endStationId]];

            // 若訖站源頭站的源頭站與訖站屬同一路線則修正時刻表及源頭
            if (!isset($this->transferRS[$endGrandparent]) || !$this->transferRS[$endGrandparent] == $endRouteId)
            {
                return;
            }
            $this->src[$endStationId] = $endGrandparent;
            $departureTime = $this->arvTimes[$endGrandparent];
            $arrival = $this->get_arrival($transportName, $endGrandparent, $endStationId, $departureTime);
            $this->set_arrival($endGrandparent, $endStationId, $arrival);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 將車站推入佇列
     */
    function add_to_queue($stationId)
    {
        try
        {
            array_push($this->queue, $stationId);

            if (($transferStationId = $this->get_transfer_station($stationId)) != null)
            {
            array_push($this->queue, $transferStationId);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 紀錄源頭
     */
    function set_source($source, $stationId)
    {
        try
        {
            $this->src[$stationId] = $source;

            if (($transferStationId = $this->get_transfer_station($stationId)) != null)
            {
                $this->src[$transferStationId] = $source;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 紀錄時刻表資料
     * @param string $source 源頭
     * @param string $stationId 車站
     * @param array $arrival 時刻表資料
     * @return void 不回傳值
     */
    function set_arrival($source, $stationId, $arrival)
    {
        try
        {
            if (!isset($this->arrivals[$source]))
            {
                $this->arrivals[$source] = [];
            }
            $this->arrivals[$source][$stationId] = $arrival;

            if (($transferStationId = $this->get_transfer_station($stationId)) != null)
            {
                $this->arrivals[$source][$transferStationId] = $arrival;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 紀錄抵達時間
     */
    function set_arrival_time($stationId, $arrivalTime)
    {
        try
        {
            $this->arvTimes[$stationId] = $arrivalTime;

            if (($transferStationId = $this->get_transfer_station($stationId)) != null)
            {
                $transferTime = $this->transferAJ[$stationId]["TransferTime"];
                $this->arvTimes[$transferStationId] = $this->add_times($arrivalTime, $transferTime);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定車站的最早抵達時間
     * @param string $stationId 車站代碼
     * @return string 最早抵達時間
     */
    function get_arrival_time($stationId, $arrivalTime = "24:59:59")
    {
        try
        {
            if (!isset($this->arvTimes[$stationId]))
            {
                $this->arvTimes[$stationId] = $arrivalTime;
            }
            return $this->arvTimes[$stationId];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 為時間加上秒數
     * @param string $time 時間
     * @param int $second 秒數
     * @return string 時間
     */
    function add_times($time, $second)
    {
        try
        {
            helper(["getTimeToSecond", "getSecondToTime"]);
            $sec1 = time_to_sec($time);
            $sec2 = $second;
            $sec3 = intval($sec1) + intval($sec2);
            return sec_to_time($sec3);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具的所有轉乘資料
     * @param string $transportName 交通工具名稱
     * @return array 轉乘資料
     */
    function get_transfers_raw($transportName)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $transfers = $this->metroModel->get_transfers()->get()->getResult();
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            return $transfers;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得轉乘站鄰接矩陣
     * @return array 轉乘站鄰接矩陣
     */
    function get_transfer_adjacency()
    {
        try
        {
            $graph = [];

            foreach ($this->transferRaw as $transfer)
            {
                $fromStationId = $transfer->from_station_id;
                $toStationId   = $transfer->to_station_id;
                $transferTime  = $transfer->transfer_time;

                if (isset($graph[$fromStationId]))
                {
                    $graph[$fromStationId] = [];
                }

                $graph[$fromStationId] = [
                    "TFStationId"  => $toStationId,
                    "TransferTime" => $transferTime
                ];
            }

            return $graph;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得車站鄰接矩陣
     * @param string $transportName 交通工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @return array 車站鄰接矩陣
     */
    function get_station_adjacencies($transportName, $startStationId, $endStationId)
    {
        try
        {
            $adjacency = [];

            // 取得起訖站所屬路線
            $startRouteId = $this->get_route($transportName, $startStationId);
            $endRouteId   = $this->get_route($transportName, $endStationId);

            // 走遍每條路線上的轉乘站
            foreach ($this->transferRS as $routeId => $stations)
            {
                // 若此筆資料非車站上的路線則表時其為車站資料
                if (gettype($stations) == "string")
                {
                    continue;
                }
                // 若造訪起站或訖站所屬路線則將起站或訖站推入車站陣列
                if (!in_array($startStationId, $stations) && $routeId == $startRouteId)
                {
                    array_unshift($stations, $startStationId);
                }
                if (!in_array($endStationId, $stations) && $routeId == $endRouteId)
                {
                    array_unshift($stations, $endStationId);
                }
                // 走遍每座車站
                for ($i = 0; $i < sizeof($stations); $i++)
                {
                    // 走遍每座車站能抵達的其他車站
                    for ($j = 0; $j < sizeof($stations); $j++)
                    {
                        if ($i == $j)
                        {
                            continue;
                        }
                        if (!isset($adjacency[$stations[$i]]))
                        {
                            $adjacency[$stations[$i]] = [];
                        }
                        $adjacency[$stations[$i]][$stations[$j]] = true;
                    }
                }
            }
            return $adjacency;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得路線上的轉乘站
     * @return array 路線車站資料
     */
    function get_transfer_route_station()
    {
        try
        {
            $graph = [];

            foreach ($this->transferRaw as $transfer)
            {
                $routeId   = $transfer->from_route_id;
                $stationId = $transfer->from_station_id;
                if (!isset($graph[$routeId]))
                {
                    $graph[$routeId] = [];
                }
                $graph[$stationId] = $routeId;
                array_push($graph[$routeId], $stationId);
            }

            return $graph;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具、起訖站及發車時間的最早班次資料
     * @param string $transportName 交通工具名稱
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $departureTime 發車時間
     * @return array 時刻表資料
     */
    function get_arrival($transportName, $fromStationId, $toStationId, $departureTime)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $transportController = $this->metroController;
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            
            // 取得時刻表資料
            try
            {
                $arrivals = $transportController->get_arrivals($fromStationId, $toStationId);
            }
            catch (Exception $e)
            {
                return [];
            }
            if (!$arrivals)
            {
                return [];
            }

            // 重新排列資料
            $transportController->restructure_arrivals($arrivals, $fromStationId, $toStationId);

            // 取得指定時刻表及發車時間的最近班次
            $arrival = $this->get_arrival_by_time($arrivals, $departureTime);

            if (!$arrival)
            {
                return [];
            }

            //回傳資料
            return $arrival;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定時刻表及發車時間的最近班次
     * @param array &$arrivals 時刻表
     * @param string $earliestDptTime 發車時間
     * @return array 時刻資訊
     */
    function get_arrival_by_time(&$arrivals, $earliestDptTime)
    {
        try
        {
            foreach ($arrivals as $arrival)
            {
                $dptTime = $arrival["Schedule"]["DepartureTime"];
                if (strcmp($dptTime, $earliestDptTime) > 0)
                {
                    return $arrival;
                }
            }
            return false;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交工具及車站的路線
     * @param string $transportName 交通工具名稱
     * @param string $stationId 車站代碼
     * @return string 路線代碼
     */
    function get_route($transportName, $stationId)
    {
        try
        {
            switch ($transportName)
            {
                case "BUS":
                    return [];
                    break;
                case "METRO":
                    $routeId = $this->metroModel->get_route_by_station($stationId)->get()->getResult();
                    break;
                case "THSR":
                    return [];
                    break;
                case "TRA":
                    return [];
                    break;
            }
            if (!isset($routeId[0]->route_id))
            {
                throw new Exception("found no route for $stationId", 400);
            }
            return $routeId[0]->route_id;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
