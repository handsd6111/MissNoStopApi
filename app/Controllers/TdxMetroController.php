<?php

namespace App\Controllers;


use App\Models\ORM\MetroArrivalModel;
use App\Models\ORM\MetroDurationModel;
use App\Models\ORM\MetroRouteModel;
use App\Models\ORM\MetroRouteStationModel;
use App\Models\ORM\MetroStationModel;
use App\Models\ORM\MetroSystemModel;
use Exception;

class TdxMetroController extends TdxBaseController
{

    // ============== Metro System ==============


    /**
     * 利用 ORM Model 取得捷運系統列表。
     * 
     * @return object[](stdClass)
        {
            MST_id => string,
            MST_name_TC => string,
            MST_name_EN => string,
        }
     */
    public function getMetroSystem()
    {
        $metroSystemModel = new MetroSystemModel();
        $result = $metroSystemModel->get()->getResult();
        return $result;
    }

    // ============== Metro Route ==============

    /**
     * 從 TDX 取得捷運路線資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass)
     * {
     *     LineNo => string
     *     LineID => string
     *     LineName => object(stdClass) {
     *         Zh_tw => string | null
     *         En => string | null
     *     }
     *     LineSectionName => object(stdClass) {}
     *     IsBranch => bool(false)
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroRoute($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Line/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的路線至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroRoute($railSystem)
    {
        $result = $this->getMetroRoute($railSystem);

        if (empty($result)) {
            return false;
        }

        foreach ($result as $value) {
            $saveData = [
                'MR_id' => $railSystem . '-' . $value->LineNo,
                'MR_name_TC' => isset($value->LineName->Zh_tw) ? $value->LineName->Zh_tw : "",
                'MR_name_EN' => isset($value->LineName->En) ? $value->LineName->En : "",
                'MR_system_id' => $railSystem
            ];
            $metroRouteModel = new MetroRouteModel();
            $metroRouteModel->save($saveData); //orm save data
        }

        return true;
    }

    /**
     * 一次性設定所有捷運路線。
     */
    public function setMetroRouteAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroRoute($metroSystem->MST_id);
        }
    }

    // ============== Metro Station ==============

    /**
     * 從 TDX 取得捷運車站資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass)
     * {
     *     LineNo => string
     *     StationUID => string 
     *     StationID => string
     *     StationName => object(stdClass) {
     *         Zh_tw => string 
     *         En => string(8) 
     *     }
     *     StationAddress => string 
     *     BikeAllowOnHoliday => bool
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     *     StationPosition => object(stdClass) {
     *         PositionLon => float
     *         PositionLat => float
     *         GeoHash => string
     *     }
     *     LocationCity => string
     *     LocationCityCode => string
     *     LocationTown => string
     *     LocationTownCode => string
     * }
     */
    public function getMetroStation($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Station/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的車站至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroStation($railSystem)
    {
        $result = $this->getMetroStation($railSystem);
        
        foreach ($result as $value) {
            $saveData = [
                'MS_id' => $value->StationUID,
                'MS_name_TC' => isset($value->StationName->Zh_tw) ? $value->StationName->Zh_tw : "",
                'MS_name_EN' => isset($value->StationName->En) ? $value->StationName->En : "",
                'MS_city_id' => $value->LocationCityCode,
                'MS_longitude' => $value->StationPosition->PositionLon,
                'MS_latitude' => $value->StationPosition->PositionLat
            ];
            // var_dump($saveData);
            $metroStationModel = new MetroStationModel();
            $metroStationModel->save($saveData); //orm save data
        }

        return true;
    }

    /**
     * 一次性設定所有捷運車站。
     */
    public function setMetroStationAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroStation($metroSystem->MST_id);
        }
    }

    // ============== Metro Duration ==============

    /**
     * 從 TDX 取得捷運車站資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass) 
     * {
     *     LineNo => string
     *     LineID => string
     *     RouteID => string
     *     TrainType => int
     *     TravelTimes => object[](stdClass) {
     *         Sequence => int
     *         FromStationID => string
     *         FromStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *         }
     *         ToStationID => string
     *         ToStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *         }
     *         RunTime => int
     *         StopTime => int
     *     }
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroDuration($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/S2STravelTime/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統的運行時間至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroDuration($railSystem)
    {
        $result = $this->getMetroDuration($railSystem);
        $metroDurationModel = new MetroDurationModel();

        // 先將支線拆開
        foreach ($result as $value) {
            $travelTimes = $value->TravelTimes; // 取得裡面的運行時間列表

            if (count($travelTimes) > 1) {
                $firstStation = $travelTimes[0]->FromStationID; // 取得此路線的起始車站
                $lastStation = $travelTimes[count($travelTimes) - 1]->ToStationID; // 取得此路線的終點車站

                for ($i = 0; $i < count($travelTimes); $i++) { // 資料內是單個車站到下個車站，故用迴圈去執行 ex:橋頭捷運站 -> 橋頭糖廠
                    if ($i == 0) { // 起始車站不做，因為會取不到上一站的值
                        continue;
                    }

                    // 防止資料中無 StopTime 此格式
                    $hasPrevStopTime = isset($travelTimes[$i - 1]->StopTime);
                    $hasNowStopTime = isset($travelTimes[$i]->StopTime);

                    // MD_end_station_id 分別代表此路線的兩個方向

                    // 此站到起始站，故運行時間是此站到上一站
                    $metroDurationModel->save([
                        'MD_station_id' => $railSystem . '-' . $travelTimes[$i]->FromStationID,
                        'MD_end_station_id' => $railSystem . '-' . $firstStation,
                        "MD_duration" => $travelTimes[$i - 1]->RunTime + $hasPrevStopTime ? $travelTimes[$i - 1]->StopTime : 0
                    ]);

                    // 此站到終點站，故運行時間是此站到下一站
                    $metroDurationModel->save([
                        'MD_station_id' => $railSystem . '-' . $travelTimes[$i]->FromStationID,
                        'MD_end_station_id' => $railSystem . '-' . $lastStation,
                        "MD_duration" => $travelTimes[$i]->RunTime + $hasNowStopTime ? $travelTimes[$i]->StopTime : 0
                    ]);
                }
            } else if (count($travelTimes) == 1) { // 一條路線中只有兩個車站，將兩站對調資料即可
                $metroDurationModel->save([
                    'MD_station_id' => $railSystem . '-' . $travelTimes[0]->FromStationID,
                    'MD_end_station_id' => $railSystem . '-' . $travelTimes[0]->ToStationID,
                    "MD_duration" => $travelTimes[0]->RunTime + $travelTimes[0]->StopTime
                ]);

                $metroDurationModel->save([
                    'MD_station_id' => $railSystem . '-' . $travelTimes[0]->ToStationID,
                    'MD_end_station_id' => $railSystem . '-' . $travelTimes[0]->FromStationID,
                    "MD_duration" => $travelTimes[0]->RunTime + $travelTimes[0]->StopTime
                ]);
            }
        }
    }

    // ============== Metro Route Station ==============

    /**
     * 從 TDX 取得捷運車站與路線之間關聯的資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass) 
     * {
     *     LineNo=>string
     *     LineID=>string
     *     Stations=>object[](stdClass) {
     *         Sequence=>int
     *         StationID=>string
     *         StationName=>object(stdClass) {
     *              Zh_tw=>string
     *              En=>string
     *         }
     *     }
     *     SrcUpdateTime=>string
     *     UpdateTime=>string
     *     VersionID=>int
     * }
     */
    public function getMetroRouteStation($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationOfLine/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入單個捷運系統車站與路線之間關聯的資料至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroRouteStation($railSystem)
    {
        $result = $this->getMetroRouteStation($railSystem);
        $metroRouteStationModel = new MetroRouteStationModel();
        $metroStationModel = new MetroStationModel();

        foreach ($result as $value) {
            $routeId = $railSystem . '-' . $value->LineNo; // 取得路線
            $stations = $value->Stations; // 取得單條路線中的所有車站
            foreach ($stations as $station) { // 利用迴圈執行車站料表中的資料
                $stationId = $railSystem . '-' . $station->StationID; // 單個車站的 ID
                // 關聯車站與路線，代表此車站在上方紀錄的 routeId 中 ex: 左營站在紅線中
                $metroRouteStationModel->save([
                    'MRS_station_id' => $stationId,
                    'MRS_route_id' => $routeId
                ]);
                $metroStationModel->save(['MS_id' => $stationId, 'MS_sequence' => $station->Sequence]);
            }
        }

        return true;
    }

    /**
     * 一次性設定所有捷運車站與路線之間關聯的資料。
     */
    public function setMetroRouteStationAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroRouteStation($metroSystem->MST_id);
        }
    }

    // ============== Metro Arrival ==============

    /**
     * 從 TDX 取得兩個捷運車站之間的時刻表資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return object[](stdClass) 
     * {
     *     RouteID => string
     *     LineID => string
     *     StationID => string
     *     StationName => object(stdClass) {
     *          Zh_tw => string
     *          En => string
     *     }
     *     Direction => int
     *     DestinationStaionID => string
     *     DestinationStationName => object(stdClass) {
     *          Zh_tw => string
     *          En => string
     *     }
     *     Timetables => object[](stdClass) {
     *          Sequence => int
     *          ArrivalTime => string
     *          DepartureTime => string
     *          TrainType => int
     *     }
     *     ServiceDay => object(stdClass) {
     *          ServiceTag => string
     *          Monday => bool
     *          Tuesday => bool
     *          Wednesday => bool
     *          Thursday => bool
     *          Friday => bool
     *          Saturday => bool
     *          Sunday => bool
     *          NationalHolidays => bool
     *     }
     *     SrcUpdateTime => string
     *     UpdateTime => string
     *     VersionID => int
     * }
     */
    public function getMetroArrival($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationTimeTable/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入兩個捷運車站之間的時刻表資料至 SQL 內。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * @return boolean true | false
     */
    public function setMetroArrival($railSystem)
    {
        $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weekday = date('w', time()); // 今天星期幾
        $day = $weekdays[$weekday]; // 將星期幾帶進去 weekdays 列表中取得字串，ex: 1 = Monday。

        $metroArrivalModel = new MetroArrivalModel();
        $result = $this->getMetroArrival($railSystem);

        foreach ($result as $value) {
            $isServiceDay = $value->ServiceDay->$day; // 比對資料中的 ServiceDay 中今天有沒有服務，若有即為true。
            $stationId = $railSystem . '-' . $value->StationID; // 取得起始車站
            $endStationId = $railSystem . '-' . $value->DestinationStaionID; //取得終點車站

            if ($isServiceDay) { // 若有服務才往下做
                $timeTables = $value->Timetables;

                // 將所有時刻紀錄至 SQL 中
                foreach ($timeTables as $timeTable) {
                    $metroArrivalModel->save([
                        'MA_station_id' => $stationId,
                        'MA_end_station_id' => $endStationId,
                        'MA_sequence' => $timeTable->Sequence,
                        'MA_remain_time' => $timeTable->ArrivalTime,
                        'MA_departure_time' => $timeTable->DepartureTime
                    ]);
                }
            }
        }

        return true;
    }
}
