<?php

namespace App\Controllers;

use App\Models\ORM\ThsrArrivalModel;
use App\Models\ORM\ThsrStationModel;
use App\Models\ORM\ThsrTrainModel;
use Exception;

class TdxThsrController extends TDXBaseController
{
    // ============== Station ==============

    /**
     * 從 TDX 取得高鐵車站資料。
     * 
     * @return object[](stdClass)
     * {
     *      StationUID => string
     *      StationID => string
     *      StationCode => string
     *      StationName => object[](stdClass) {
     *          Zh_tw => string
     *          En => string
     *      }
     *      StationAddress => string
     *      OperatorID => string
     *      UpdateTime => string
     *      VersionID => int
     *      StationPosition => object[](stdClass) {
     *          PositionLon => float
     *          PositionLat => float
     *          GeoHash => string
     *      }
     *      LocationCity => string
     *      LocationCityCode => string
     *      LocationTown => string
     *      LocationTownCode => string
     * }
     */
    public function getThsrStation()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/THSR/Station?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入高鐵車站至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setThsrStation()
    {
        $result = $this->getThsrStation();
        $thsrSataionModel = new ThsrStationModel();

        foreach ($result as $value) {
            $thsrSataionModel->save([
                'HS_id' => $value->StationUID,
                'HS_name_TC' => $value->StationName->Zh_tw,
                'HS_name_EN' => $value->StationName->En,
                'HS_city_id' => $value->LocationCityCode,
                'HS_longitude' => $value->StationPosition->PositionLon,
                'HS_latitude' => $value->StationPosition->PositionLat
            ]);
        }

        return true;
    }

    // ============== Train and Arrival ==============

    /**
     * 從 TDX 的單個資料表中取得車次與時刻表資料
     * 
     * @return object[](stdClass) 
     * {
     *      TrainDate => string
     *      DailyTrainInfo => object(stdClass) {
     *          TrainNo => string
     *          Direction => int
     *          StartingStationID => string
     *          StartingStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *          }
     *          EndingStationID => string
     *          EndingStationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *          }
     *          Note => object(stdClass) {}
     *      }
     *      StopTimes => object[](stdClass) {
     *          StopSequence => int
     *          StationID => string
     *          StationName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *          }
     *          ArrivalTime => string
     *          DepartureTime => string
     *      }
     *      UpdateTime => string
     *      VersionID => int
     * }

     */
    public function getThsrTrainAndArrival()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/THSR/DailyTimetable/Today?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }


    /**
     * 利用 ORM Model 寫入高鐵車次至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setThsrTrain()
    {
        $result = $this->getThsrTrainAndArrival();
        $thsrTrainModel = new ThsrTrainModel();

        foreach ($result as $value) {
            $thsrTrainModel->save([
                'HT_id' => $value->DailyTrainInfo->TrainNo,
                'HT_departure_time' => $value->TrainDate
            ]);
        }

        return true;
    }

    /**
     * 利用 ORM Model 寫入高鐵時刻表至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setThsrArrival()
    {
        $result = $this->getThsrTrainAndArrival();
        $thsrArrivalModel = new ThsrArrivalModel();

        foreach ($result as $value) {
            foreach ($value->StopTimes as $stopTime) {
                $thsrArrivalModel->save([
                    'HA_train_id' => $value->DailyTrainInfo->TrainNo,
                    'HA_station_id' => 'THSR-' . $stopTime->StationID,
                    'HA_arrival_time' => $stopTime->ArrivalTime
                ]);
            }
        }
    }

    /**
     * 利用 ORM Model 寫入高鐵車次與時刻表至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setThsrTrainAndArrival()
    {
        $result = $this->getThsrTrainAndArrival();
        $thsrArrivalModel = new ThsrArrivalModel();
        $thsrTrainModel = new ThsrTrainModel();
        foreach ($result as $value) {
            $thsrTrainModel->save([
                'HT_id' => $value->DailyTrainInfo->TrainNo,
                'HT_departure_time' => $value->TrainDate
            ]);

            foreach ($value->StopTimes as $stopTime) {
                $thsrArrivalModel->save([
                    'HA_train_id' => $value->DailyTrainInfo->TrainNo,
                    'HA_station_id' => 'THSR-' . $stopTime->StationID,
                    'HA_arrival_time' => $stopTime->ArrivalTime
                ]);
            }
        }
    }

}
