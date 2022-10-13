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
     * 車站最早發車時間（用於演算法）
     * $this->dptTimes = [
     *      StationId: 發車時間,
     * ];
     */
    private $dptTimes;

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
            // 載入幫手
            helper(["getSecondToTime", "getTimeToSecond"]);

            // 載入轉乘資料
            $this->transferRaw = $this->get_transfers_raw($transportName);
            $this->transferAJ  = $this->get_transfer_adjacency($this->transferRaw);
            $this->transferRS  = $this->get_transfer_route_station($this->transferRaw);
            $this->stationAJ   = $this->get_station_adjacency($this->transferRS);

            // 若起訖站已於同一條路線上則直接使用 Arrivals 資料
            if ($this->is_on_same_route($startStationId, $endStationId))
            {
                return $this->get_arrival($transportName, $startStationId, $endStationId, $departureTime);
            }

            $startRouteId = $this->get_route($transportName, $startStationId); // 起站路線
            $endRouteId   = $this->get_route($transportName, $endStationId);   // 訖戰路線
            $this->queue        = [$startStationId];         // 車站佇列
            $this->src          = ["$startStationId" => -1]; // 車站源頭
            $this->dptTimes     = ["$startStationId" => time_to_sec($departureTime)]; // 最早發車時間紀錄
            $this->arrivals     = []; // 時刻表紀錄
            $this->visited      = []; // 已造訪車站

            // 拜訪位於起站路線上的所有轉乘站
            foreach ($this->transferRS[$startRouteId] as $tfStation)
            {
                // 將轉乘站推入車站佇列
                array_push($this->queue, $tfStation);

                $tfTfStationId = $this->transferAJ[$tfStation]["TFStationId"];

                // 嘗試取得起站至轉乘站的時刻表
                try
                {
                    $dptTime = sec_to_time($this->dptTimes[$startStationId]);
                    $arrival = $this->get_arrival($transportName, $startStationId, $tfStation, $dptTime);
                }
                catch (Exception $e)
                {
                    continue;
                }
                if (!$arrival)
                {
                    continue;
                }

                // 將轉乘站的源頭設為起站
                $this->src[$tfStation] = $startStationId;
                $this->src[$tfTfStationId] = $startStationId;

                // 紀錄時刻表
                $this->arrivals[$tfStation] = $arrival;
                $this->arrivals[$tfTfStationId] = $arrival;

                $transferTime = intval($this->transferAJ[$tfStation][$tfTfStationId]);
                $arrivalTime = time_to_sec($arrival["Schedule"]["ArrivalTime"]) + $transferTime;

                // 紀錄轉乘站的最早發車時間
                $this->dptTimes[$tfStation] = $arrivalTime;
                $this->dptTimes[$tfTfStationId] = $arrivalTime;
            }

            foreach ($this->dptTimes as $stationId => $dptTime)
            {
                var_dump($stationId . " => " . sec_to_time($dptTime));
            }

            $safeLoops = 100;

            array_shift($this->queue);
            $this->visited[$startStationId] = true;

            while ($this->queue)
            {
                if (--$safeLoops < 0)
                {
                    throw new Exception("1infinite loop detected", 1);
                }

                $srcStationId   = array_shift($this->queue);
                $srcTfStationId = $this->transferAJ[$srcStationId]["TFStationId"];
                
                $this->visited[$srcStationId] = true;
                
                foreach ($this->stationAJ[$srcTfStationId] as $tfStation)
                {
                    // 拜訪車站
                    $this->visit_station($transportName, $srcStationId, $srcTfStationId, $tfStation);
                }
            }

            foreach ($this->transferRS[$endRouteId] as $tfStation)
            {
                $arrival = $this->get_arrival($transportName, $tfStation, $endStationId, sec_to_time($this->dptTimes[$tfStation]));
                $tfDptTime = time_to_sec($arrival["Schedule"]["DepartureTime"]);

                if (!isset($this->dptTimes[$endStationId]) || $tfDptTime < $this->dptTimes[$endStationId])
                {
                    $this->src[$endStationId] = $tfStation;
                    $this->arrivals[$endStationId] = $arrival;
                    $this->dptTimes[$endStationId] = $tfDptTime = time_to_sec($arrival["Schedule"]["ArrivalTime"]);
                }
            }

            // 若訖站即轉乘站則需排除邏輯問題
            // if ()
            // {

            // }

            $endStationSrcId = $this->src[$this->src[$endStationId]];
            $endTfStationSrcId = $this->transferAJ[$endStationSrcId]["TFStationId"];

            $this->arrivals[$endStationId] = $this->get_arrival($transportName, $endTfStationSrcId, $endStationId, sec_to_time($this->dptTimes[$this->src[$endStationId]]));

            $this->src[$endStationId] = $endStationSrcId;

            $srcStationId = $endStationId;

            $arrivals = [];

            $safeLoops = 100;

            while($srcStationId != -1)
            {
                if (--$safeLoops < 0)
                {
                    throw new Exception("2infinite loop detected", 1);
                }
                if (!isset($this->arrivals[$srcStationId]))
                {
                    break;
                }
                array_unshift($arrivals, $this->arrivals[$srcStationId]);

                $srcStationId = $this->src[$srcStationId];
            }

            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 源頭站拜訪車站
     * @param string $transportName  交通工具名稱
     * @param string $srcStationId   源頭站代碼
     * @param string $srcTfStationId 源頭轉乘站代碼
     * @param string $vzStationId    拜訪站代碼
     * @return void 不回傳值
     */
    function visit_station($transportName, $srcStationId, $srcTfStationId, $vzStationId)
    {
        try
        {
            // 若已拜訪此源頭站則跳過
            if (isset($this->visited[$srcTfStationId]))
            {
                return;
            }

            // 取得從源頭轉乘站開往拜訪站的時刻表（部分北捷路線無資料）
            try
            {
                $arrival = $this->get_arrival($transportName, $srcTfStationId, $vzStationId, sec_to_time($this->dptTimes[$srcStationId]));
            }
            catch (Exception $e)
            {
                return;
            }
            if (!$arrival)
            {
                return;
            }
            // 取得拜訪站的轉乘時間
            $transferTime = intval($this->transferAJ[$srcStationId][$srcTfStationId]);

            // 取得拜訪站的最早發車時間（源頭站開往拜訪站的抵達時間 + 轉乘時間）
            $tfDptTime = time_to_sec($arrival["Schedule"]["DepartureTime"]);
    
            // 若此拜訪站的轉乘站最早發車時間是前所未有地早
            if (isset($this->dptTimes[$vzStationId]) && $tfDptTime >= $this->dptTimes[$vzStationId])
            {
                return;
            }

            // 將拜訪站的轉乘站列入車站佇列
            array_push($this->queue, $vzStationId);

            // 將拜訪站的源頭設為源頭站
            $this->src[$vzStationId] = $srcStationId;

            // 將源頭站的時刻表設為「從源頭站開往拜訪站的時刻表」
            $this->arrivals[$vzStationId] = $arrival;

            // 將拜訪站的發車時間設為「源頭站的抵達時間 + 拜訪站的轉乘時間」
            $this->dptTimes[$vzStationId] = time_to_sec($arrival["Schedule"]["ArrivalTime"]) + $transferTime;
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
     * 取得路線轉乘資料
     */
    function get_transfer_adjacency(&$transferRaw)
    {
        try
        {
            $graph = [];

            foreach ($transferRaw as $transfer)
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
                    "$toStationId" => $transferTime
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
     * 檢查兩車站是否處於同一條路線上
     */
    function is_on_same_route($fromStationId, $toStationId)
    {
        try
        {
            $routeId = $this->metroModel->is_on_same_route($fromStationId, $toStationId)->get()->getResult()[0]->route_id;
            return $routeId;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_station_adjacency(&$transferRS)
    {
        try
        {
            $adjacency = [];

            foreach ($transferRS as $routeId => $stations)
            {
                for ($i = 0; $i < sizeof($stations); $i++)
                {
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
                        array_push($adjacency[$stations[$i]], $stations[$j]);
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

    function get_transfer_route_station(&$transferRaw)
    {
        try
        {
            $graph = [];

            foreach ($transferRaw as $transfer)
            {
                $routeId       = $transfer->from_route_id;
                $fromStationId = $transfer->from_station_id;
                if (!isset($graph[$routeId]))
                {
                    $graph[$routeId] = [];
                }
                array_push($graph[$routeId], $fromStationId);
            }

            return $graph;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定交通工具、起訖站及發車時間的最近班次資料
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
            $arrivals = $transportController->get_arrivals($fromStationId, $toStationId);

            // 重新排列資料
            $transportController->restructure_arrivals($arrivals, $fromStationId, $toStationId);

            // 取得指定時刻表及發車時間的最近班次
            $arrival = $this->get_arrival_by_time($arrivals, $departureTime);

            //回傳資料
            return $arrival;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     * 取得指定時刻表及發車時間的最近班次
     * @param array &$arrivals 時刻表
     * @param string $time 時間
     * @return array 時刻資訊
     */
    function get_arrival_by_time(&$arrivals, $time)
    {
        try
        {
            helper("getSecondFromTime");

            $timeSec = sec_to_time($time);

            foreach ($arrivals as $arrival)
            {
                if (sec_to_time($arrival["Schedule"]["DepartureTime"]) >= $timeSec)
                {
                    return $arrival;
                }
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

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
                throw new Exception("found no route for $stationId", 1);
            }
            return $routeId[0]->route_id;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
