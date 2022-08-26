<?php

namespace App\Controllers;

use App\Models\ORM\CityModel;
use App\Models\ORM\MetroArrivalModel;
use App\Models\ORM\MetroDurationModel;
use App\Models\ORM\MetroRouteModel;
use App\Models\ORM\MetroRouteStationModel;
use App\Models\ORM\MetroStationModel;
use App\Models\ORM\MetroSystemModel;
use App\Models\TDXAuth;
use Exception;
use \Config\Services as CS;

class TDXDataController extends TDXBaseController
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

    // ============== City ==============

    /**
     * 從 TDX 取得城市資料，並且利用 ORM Model 寫入 SQL 內。
     * 
     * @return boolean true | false
     */
    public function getAndSetCities()
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Basic/City?%24format=JSON";
            $result = $this->curlGet($url, $accessToken);
            var_dump($result[0]->CityID);
            foreach ($result as $value) {

                $saveData = [
                    'C_id' => $value->CityCode,
                    'C_name_TC' => $value->CityName,
                    'C_name_EN' => $value->City
                ];

                $cityModel = new CityModel();
                $cityModel->save($saveData); //orm save data
            }
        } catch (Exception $ex) {
            log_message("critical", $ex->getMessage());
            return false;
        }

        return true;
    }

    // ============== Metro Route ==============

    /**
     * 從 TDX 取得捷運路線資料。
     * 
     * @param string $railSystem 捷運系統 ex：'KRTC'
     * 
     * @return object[](stdClass)
        {
            LineNo => string
            LineID => string
            LineName => object[](stdClass) {
                Zh_tw => string | null
                En => string | null
            }
            LineSectionName => object(stdClass) {}
            IsBranch => bool(false)
            SrcUpdateTime => string
            UpdateTime => string
            VersionID => int
        }
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

    public function setMetroRouteAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroRoute($metroSystem->MST_id);
        }
    }

    // ============== Metro Station ==============


    public function getMetroStation($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Station/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

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

    public function setMetroStationAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroStation($metroSystem->MST_id);
        }
    }

    // ============== Metro Duration ==============


    public function getMetroDuration($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/S2STravelTime/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    public function setMetroDuration($railSystem)
    {
        $result = $this->getMetroDuration($railSystem);
        $metroDurationModel = new MetroDurationModel();

        //先將支線拆開
        foreach ($result as $value) {
            $travelTimes = $value->TravelTimes; //取得裡面的運行時間列表

            if (count($travelTimes) > 1) {
                $firstStation = $travelTimes[0]->FromStationID;
                $lastStation = $travelTimes[count($travelTimes) - 1]->ToStationID;
                for ($i = 0; $i < count($travelTimes); $i++) {
                    if ($i == 0) {
                        continue;
                    }

                    $hasPrevStopTime = isset($travelTimes[$i - 1]->StopTime);
                    $hasNowStopTime = isset($travelTimes[$i]->StopTime);

                    $metroDurationModel->save([
                        'MD_station_id' => $railSystem . '-' . $travelTimes[$i]->FromStationID,
                        'MD_end_station_id' => $railSystem . '-' . $firstStation,
                        "MD_duration" => $travelTimes[$i - 1]->RunTime + $hasPrevStopTime ? $travelTimes[$i - 1]->StopTime : 0
                    ]);

                    $metroDurationModel->save([
                        'MD_station_id' => $railSystem . '-' . $travelTimes[$i]->FromStationID,
                        'MD_end_station_id' => $railSystem . '-' . $lastStation,
                        "MD_duration" => $travelTimes[$i]->RunTime + $hasNowStopTime ? $travelTimes[$i]->StopTime : 0
                    ]);
                }
            } else if (count($travelTimes) == 1) { //一個個別處理，將起始與末站對調即可
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


    public function getMetroRouteStation($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationOfLine/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    public function setMetroRouteStation($railSystem)
    {
        $result = $this->getMetroRouteStation($railSystem);
        $metroRouteStationModel = new MetroRouteStationModel();

        foreach ($result as $value) {
            $routeId = $railSystem . '-' . $value->LineNo;
            $stations = $value->Stations;
            foreach ($stations as $station) {
                $stationId = $railSystem . '-' . $station->StationID;
                $metroRouteStationModel->save([
                    'MRS_station_id' => $stationId,
                    'MRS_route_id' => $routeId
                ]);
            }
        }

        return true;
    }

    public function setMetroRouteStationAll()
    {
        $metroSystems = $this->getMetroSystem();
        foreach ($metroSystems as $metroSystem) {
            $this->setMetroRouteStation($metroSystem->MST_id);
        }
    }

    // ============== Metro Arrival ==============

    public function getMetroArrival($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationTimeTable/$railSystem?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    public function deleteMetroArrivalAllData()
    {
        $metroArrivalModel = new MetroArrivalModel();
        $metroArrivalModel->delete();

        return true;
    }

    public function setMetroArrival($railSystem)
    {
        $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weekday = date('w', time()); // 今天星期幾
        $day = $weekdays[$weekday];

        // $result = $this->getMetroArrival($railSystem);

        $metroArrivalModel = new MetroArrivalModel();
        // return var_dump($metroArrivalModel->test());
        $result = $this->getMetroArrival($railSystem);

        foreach ($result as $value) {
            $isServiceDay = $value->ServiceDay->$day;
            $stationId = $railSystem . '-' . $value->StationID;
            $endStationId = $railSystem . '-' . $value->DestinationStaionID;

            if ($isServiceDay) {
                $timeTables = $value->Timetables;

                foreach ($timeTables as $timeTable) {
                    $metroArrivalModel->save([
                        'MA_station_id' => $stationId,
                        'MA_end_station_id' => $endStationId,
                        'MA_sequence' => $timeTable->Sequence,
                        'MA_arrival_time' => $timeTable->ArrivalTime,
                        'MA_departure_time' => $timeTable->DepartureTime
                    ]);
                }
            }
        }

        return true;
    }
}
