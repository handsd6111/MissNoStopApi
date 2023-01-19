<?php

namespace App\Controllers;

use App\Controllers\ApiBaseController;
use App\Controllers\ApiMetroBaseController;
use App\Models\MetroModel;
use Exception;

/**
 * 路線規劃 API 底層控制器
 */
class ApiRoutePlanBaseController extends ApiBaseController
{
    protected $startStationId;
    protected $endStationId;
    protected $startRoutes;
    protected $endRoutes;
    protected $departureTime;
    protected $arvTimes;
    public $stationRoutes;

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
     *          station_id:          起站代碼,
     *          transfer_station_id: 訖站代碼,
     *          transfer_time:       轉乘耗費時間（秒）
     *      },
     * ];
     */
    private $transferRaw;

    /**
     * @var array 轉乘車站鄰接矩陣
     * $this->transferData = [
     *      StationId: [           車站代碼（例：TRTC-R13）
     *          TransferStationId: 轉乘站代碼（例：TRTC-O11）,
     *          TransferTime:      轉乘耗費時間（秒）
     *      ],
     * ];
     */
    private $transferData;

    /**
     * @var array 轉乘路線車站
     * $this->routeStations = [
     *      SubRouteId: [  路線代碼
     *          StationId: 車站代碼,
     *      ]
     * ];
     */
    private $routeStations;

    /**
     * @var array 同一條路線上的轉乘車站鄰接矩陣
     * $this->stationAdjacency = [
     *      StationId: [
     *          AdjacentStationId: 鄰接車站代碼,
     *      ],
     * ];
     */
    private $stationAdjacency;

    /**
     * 已拜訪車站（用於演算法）
     * $this->visited = [
     *      StationId: 運行時間
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
     */
    private $arrivals;

    public $metroModel;
    public $metroController;

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
     * 取得路線規劃資料
     * @param string $startStationId 起站代碼
     * @param string $endStationId 訖站代碼
     * @return array 路線規劃資料
     */
    function get_route_plan($startStationId, $endStationId, $departureTime)
    {
        try
        {
            $this->startStationId = $startStationId;
            $this->endStationId   = $endStationId;
            $this->departureTime  = $departureTime;
            try
            {
                $arrival = $this->get_arrival($startStationId, $endStationId, $departureTime);

                if (!$arrival)
                {
                    return $this->get_cross_route_plan();
                }
                $this->restructure_arrival($arrival, $startStationId, $endStationId);

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
            throw $e;
        }
    }

    function get_cross_route_plan()
    {
        try
        {
            // 初始化演算法資料
            $this->queue    = [$this->startStationId];           // 車站佇列
            $this->src      = ["{$this->startStationId}" => -1]; // 車站源頭
            $this->arvTimes = ["{$this->startStationId}" => $this->departureTime]; // 最早抵達時間
            $this->arrivals = []; // 時刻表紀錄
            $this->visited  = []; // 已造訪車站

            // 初始化轉乘資料
            $this->innitialize_algorithm_data();

            while ($this->queue)
            {
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
                $this->visit_neighbors($source);
            }
            // 修正起站源頭
            $this->src[$this->startStationId] = -1;

            // 從訖站一路至起站地逆向查詢時刻表資料
            $arrivals = $this->retrace_source($this->endStationId);

            $this->fix_duplicate_sub_route($arrivals);

            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 初始化轉乘資料
     * @return void 不回傳值
     */
    function innitialize_algorithm_data()
    {
        try
        {
            // 初始化演算法所需資料
            $this->transferRaw   = $this->metroModel->get_transfers()->get()->getResult();
            $this->transferData  = [];
            $this->routeStations = [];
            
            // 取得起訖站的所有子路線
            $this->startRoutes = $this->get_sub_routes($this->startStationId);
            $this->endRoutes   = $this->get_sub_routes($this->endStationId);

            // 初始化車站鄰接矩陣及子路線車站對應資料
            foreach ($this->transferRaw as $index => $data)
            {
                $stationId    = $data->station_id;
                $TFStationId  = $data->transfer_station_id;
                $transferTime = $data->transfer_time;
                $this->set_transfer_data($stationId, $TFStationId, $transferTime);
                $this->set_route_stations($stationId);
            }
            // 初始化車站鄰接矩陣
            $this->set_station_adjacencies();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 將指定起訖站寫入車站矩陣
     */
    function set_transfer_data($stationId, $transferStationId, $transferTime)
    {
        try
        {
            $this->transferData[$stationId] = [
                "TransferStationId" => $transferStationId,
                "TransferTime"    => $transferTime
            ];
            $this->transferData[$transferStationId] = [
                "TransferStationId" => $stationId,
                "TransferTime"    => $transferTime
            ];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 將指定車站寫入「子路線車站」矩陣
     * @return array 路線車站資料
     */
    function set_route_stations($stationId)
    {
        try
        {
            $subRoutes = $this->metroModel->get_sub_routes($stationId)->get()->getResult();

            foreach ($subRoutes as $index => $subRouteId)
            {
                $subRoutes[$index] = $subRouteId->sub_route_id;
            }
            $this->stationRoutes[$stationId] = $subRoutes;

            foreach ($subRoutes as $index => $subRouteId)
            {
                $this->set_route_station($subRouteId, $stationId, $subRoutes);
                $this->set_route_station($subRouteId, $this->startStationId, $this->startRoutes);
                $this->set_route_station($subRouteId, $this->endStationId, $this->endRoutes);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 寫入路線對應車站資料
     */
    function set_route_station($subRouteId, $stationId, $subRoutes)
    {
        try
        {
            if (!isset($this->routeStations[$subRouteId]))
            {
                $this->routeStations[$subRouteId] = [];
            }
            if (!in_array($subRouteId, $subRoutes))
            {
                return;
            }
            array_push($this->routeStations[$subRouteId], $stationId);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 初始化車站鄰接矩陣
     */
    function set_station_adjacencies()
    {
        try
        {
            foreach ($this->routeStations as $routeId => $stationIds)
            {
                for ($i = 0; $i < sizeof($stationIds); $i++)
                {
                    $fromStationId = $stationIds[$i];

                    for ($j = 0; $j < sizeof($stationIds); $j++)
                    {
                        if ($i == $j)
                        {
                            continue;
                        }
                        $toStationId = $stationIds[$j];

                        if ($fromStationId == $toStationId)
                        {
                            continue;
                        }
                        $this->set_station_adjacency($fromStationId, $toStationId);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 將指定起訖站寫入車站鄰接矩陣
     */
    function set_station_adjacency($fromStationId, $toStationId)
    {
        try
        {
            if (!isset($this->stationAdjacency[$fromStationId]))
            {
                $this->stationAdjacency[$fromStationId] = [];
            }
            $this->stationAdjacency[$fromStationId][$toStationId] = true;
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
            if (isset($this->transferData[$stationId]["TransferStationId"]))
            {
                return $this->transferData[$stationId]["TransferStationId"];
            }
            return null;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 拜訪所有鄰居
     * @param string $source 源頭車站代碼
     * @return void 不回傳值
     */
    function visit_neighbors($source)
    {
        try
        {
            // 走訪源頭站所有鄰居站（源頭站與鄰居站同屬一路線）
            foreach ($this->stationAdjacency[$source] as $neighbor => $connected)
            {
                // 若已造訪此鄰居站則跳過
                if (isset($this->visited[$neighbor]))
                {
                    continue;
                }
                // 拜訪鄰居
                $visitSuccess = $this->visit_neighbor($source, $neighbor);

                if (!$visitSuccess)
                {
                    continue;
                }
                // 將鄰居及其轉乘站（若有）推入佇列
                $this->add_to_queue($neighbor);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 拜訪指定鄰居
     * @param string $source 源頭車站代碼
     * @param string $neighbor 鄰居車站代碼
     * @return bool 是否拜訪成功
     */
    function visit_neighbor($source, $neighbor)
    {
        try
        {
            // 取得源頭站發車時間
            $departureTime = $this->get_arrival_time($source);

            // 時刻表：源頭 -> 鄰居
            $arrival = $this->get_arrival($source, $neighbor, $departureTime);

            // 若查無時刻表則跳過
            if (!$arrival)
            {
                return false;
            }
            // 取得鄰居站新舊抵達時間
            $arrivalTime    = $arrival->arrival_time;
            $oldArrivalTime = $this->get_arrival_time($neighbor);

            // 若鄰居的抵達時間不是前所未有的早則跳過
            if (isset($this->src[$neighbor]) && strcmp($arrivalTime, $oldArrivalTime) > 0)
            {
                return false;
            }
            // 更新鄰居其轉乘站（若有）的演算法資料
            $this->set_arrival_time($neighbor, $arrivalTime);
            $this->set_source($source, $neighbor);
            $this->set_arrival($source, $neighbor, $arrival);

            return true;
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

            if (!isset($this->src[$endStationId]))
            {
                throw new Exception("車站 $stationId 尚未開放查詢", 400);
            }
            while ($this->src[$stationId] != -1)
            {
                $source = $this->src[$stationId];
                $arrival = $this->arrivals[$source][$stationId];
                $this->restructure_arrival($arrival, $source, $stationId);
                array_unshift($arrivals, $arrival);
                $stationId = $source;
            }
            return $arrivals;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function restructure_arrival(&$arrival, $fromStationId, $toStationId)
    {
        try
        {
            $routeId       = $arrival->route_id;
            $subRouteId    = $arrival->sub_route_id;

            $routeData       = $this->metroModel->get_route($routeId)->get()->getResult()[0];
            $subRouteData    = $this->metroModel->get_sub_route($subRouteId)->get()->getResult()[0];
            $fromStationData = $this->metroModel->get_station($fromStationId)->get()->getResult()[0];
            $toStationData   = $this->metroModel->get_station($toStationId)->get()->getResult()[0];

            $arrival = [
                "RouteId" => $routeId,
                "RouteName" => [
                    "TC" => $routeData->name_TC,
                    "EN" => $routeData->name_EN
                ],
                "SubRouteId" => $subRouteId,
                "SubRouteName" => [
                    "TC" => $subRouteData->name_TC,
                    "EN" => $subRouteData->name_EN
                ],
                "FromStationId" => $fromStationId,
                "FromStationName" => [
                    "TC" => $fromStationData->name_TC,
                    "EN" => $fromStationData->name_EN
                ],
                "ToStationId" => $toStationId,
                "ToStationName" => [
                    "TC" => $toStationData->name_TC,
                    "EN" => $toStationData->name_EN
                ],
                "Schedule" => [
                    "DepartureTime" => $arrival->departure_time,
                    "ArrivalTime"   => $arrival->arrival_time,
                    "Duration"      => $arrival->duration
                ]
            ];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 修正子路線重複問題
     * @param array &$arrivals 時刻表
     * @return void 不回傳值
     */
    function fix_duplicate_sub_route(&$arrivals)
    {
        try
        {
            $size = sizeof($arrivals);

            if ($size < 2) return;

            $secLastIndex = $size - 2;
            $lastIndex    = $size - 1;
            $secLastArrival = $arrivals[$secLastIndex];
            $lastArrival    = $arrivals[$lastIndex];

            if ($secLastArrival["RouteId"] != $lastArrival["RouteId"] && $secLastArrival["SubRouteId"] == $lastArrival["SubRouteId"]) return;  

            $fromStationId = $secLastArrival["FromStationId"];
            $toStationId   = $lastArrival["ToStationId"];
            $departureTime = $secLastArrival["Schedule"]["DepartureTime"];

            $newArrival = $this->get_arrival($fromStationId, $toStationId, $departureTime);

            $this->restructure_arrival($newArrival, $fromStationId, $toStationId);

            if (!$newArrival) return;

            $arrivals = array_slice($arrivals, 0, $secLastIndex);
            array_push($arrivals, $newArrival);
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
            helper("addTime");
            $this->arvTimes[$stationId] = $arrivalTime;

            if (($transferStationId = $this->get_transfer_station($stationId)) != null)
            {
                $transferTime = $this->transferData[$stationId]["TransferTime"];
                $this->arvTimes[$transferStationId] = add_time($arrivalTime, $transferTime);
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
     * 取得指定交通工具、起訖站及發車時間的最早班次資料
     * @param string $fromStationId 起站代碼
     * @param string $toStationId 訖站代碼
     * @param string $arrivalTime 發車時間
     * @return object 時刻表資料
     */
    function get_arrival($fromStationId, $toStationId, $arrivalTime)
    {
        try
        {
            // 取得時刻表資料
            try
            {
                // 取得行駛方向
                $direction = $this->metroController->get_direction($fromStationId, $toStationId);

                // 取得起訖站皆行經的所有捷運子路線
                $subRoutes = $this->metroController->get_sub_routes_by_stations($fromStationId, $toStationId, $direction);

                // 取得指定子路線的總運行時間
                $durations = $this->metroController->get_durations($fromStationId, $toStationId, $subRoutes, $direction);
                
                // 取得時刻表
                $arrival = $this->metroController->get_arrivals($fromStationId, $direction, $subRoutes, $durations, $arrivalTime)[0];
            }
            catch (Exception $e)
            {
                return [];
            }
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
     * 取得指定車站的子路線
     * @param string $stationId 車站代碼
     * @return string 子路線代碼
     */
    function get_sub_routes($stationId)
    {
        try
        {
            $subRoutes = $this->metroModel->get_sub_routes($stationId)->get()->getResult();

            if (!sizeof($subRoutes))
            {
                throw new Exception("查無 $stationId 所屬的子路線", 400);
            }
            foreach ($subRoutes as $index => $subRoute)
            {
                $subRoutes[$index] = $subRoute->sub_route_id;
            }
            return $subRoutes;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
