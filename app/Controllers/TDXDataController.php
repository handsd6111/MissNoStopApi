<?php

namespace App\Controllers;

use App\Models\ORM\CityModel;
use App\Models\ORM\MetroDurationModel;
use App\Models\ORM\MetroRouteModel;
use App\Models\ORM\MetroStationModel;
use App\Models\TDXAuth;
use Exception;
use \Config\Services as CS;

class TDXDataController extends TDXBaseController
{


    /**
     * 
     */
    public function getAndSetCities()
    {
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

        return true;
    }

    public function getAndSetMetroRoute($railSystem)
    {
        $accessToken = $this->getAccessToken();
        // $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/StationTimeTable/TRTC?format=JSON";
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Line/$railSystem?%24format=JSON";

        $result = $this->curlGet($url, $accessToken);
        // var_dump($result);
        foreach ($result as $value) {

            $saveData = [
                'MR_id' => $railSystem . '-' . $value->LineNo,
                'MR_name_TC' => isset($value->LineName->Zh_tw) ? $value->LineName->Zh_tw : "",
                'MR_name_EN' => isset($value->LineName->En) ? $value->LineName->En : "",
            ];
            // var_dump($saveData);
            $metroRouteModel = new MetroRouteModel();
            $metroRouteModel->save($saveData); //orm save data
        }

        return false;
    }


    public function getAndSetMetroStation($railSystem)
    {
        $accessToken = $this->getAccessToken();

        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/Station/$railSystem?%24format=JSON";

        $result = $this->curlGet($url, $accessToken);

        // var_dump($result);

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
    }

    public function getAndSetMetroDuration($railSystem)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/Metro/S2STravelTime/$railSystem?%24format=JSON";
        $result = $this->curlGet($url, $accessToken);

        $metroDurationModel = new MetroDurationModel();

        // return var_dump($result);

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
}
