<?php

namespace App\Controllers;

use App\Models\ORM\TraArrivalModel;
use App\Models\ORM\TraRouteModel;
use App\Models\ORM\TraRouteStationModel;
use App\Models\ORM\TraStationModel;
use App\Models\ORM\TraTrainModel;

class TdxTraController extends TdxBaseController
{
    // ============== Station ==============

    /**
     * 從 TDX 取得台鐵車站資料。
     * 
     * @return object[](stdClass)
     * {
     *      StationUID => string
     *      StationID => string
     *      StationName => object(stdClass) {
     *          Zh_tw => string
     *          En => string
     *      }
     *      StationAddress => string
     *      StationPhone => string
     *      OperatorID => string
     *      StationClass => string
     *      UpdateTime => string
     *      VersionID => int
     *      StationPosition => object(stdClass) {
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
    public function getTraStation()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/TRA/Station?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入台鐵車站資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraStation()
    {
        $result = $this->getTraStation();
        $traStationModel = new TraStationModel();

        foreach ($result as $value) {
            $traStationModel->save([
                'RS_id' => $value->StationUID,
                'RS_name_TC' => $value->StationName->Zh_tw,
                'RS_name_EN' => $value->StationName->En,
                'RS_city_id' => $value->LocationCityCode,
                'RS_longitude' => $value->StationPosition->PositionLon,
                'RS_latitude' => $value->StationPosition->PositionLat
            ]);
        }

        return true;
    }

    // ============== Route ==============

    /**
     * 從 TDX 取得台鐵路線資料。
     * 
     * @return object[](stdClass)#80 (8) 
     * {
     *      LineNo => string
     *      LineID => string
     *      LineNameZh => string
     *      LineNameEn => string
     *      LineSectionNameZh => string
     *      LineSectionNameEn => string
     *      IsBranch => bool
     *      UpdateTime => string
     * }
     */
    public function getTraRoute()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/TRA/Line?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入台鐵路線資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraRoute()
    {
        $result = $this->getTraRoute();
        $traRouteModel = new TraRouteModel();

        foreach ($result as $value) {
            $traRouteModel->save([
                'RR_id' => $value->LineID,
                'RR_name_TC' => $value->LineNameZh,
                'RR_name_EN' => $value->LineNameEn
            ]);
        }
    }

    // ============== Route Station ==============

    /**
     * 從 TDX 取得台鐵車站與路線關聯的資料。
     * 
     * @return object[](stdClass) 
     * {
     *      LineNo => string
     *      LineID => string
     *      Stations => object[](stdClass) {
     *          Sequence => int
     *          StationID => string
     *          StationName => string
     *          TraveledDistance => float
     *      }
     *      UpdateTime => string
     * }
     */
    public function getTraRouteStation()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/TRA/StationOfLine?%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入台鐵車站與路線關聯的資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraRouteStation()
    {
        $result = $this->getTraRouteStation();
        $traRouteStationModel = new TraRouteStationModel();

        foreach ($result as $value) {
            foreach ($value->Stations as $station) {
                $traRouteStationModel->save([
                    'RRS_station_id' => 'TRA-' . $station->StationID,
                    'RRS_route_id' => $value->LineID,
                    'RRS_sequence' => $station->Sequence
                ]);
            }
        }

        return 1;
    }

    // ============== Train ==============

    /**
     * 從 TDX 取得台鐵車次資料。
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
     *          TrainTypeID => string
     *          TrainTypeCode => string
     *          TrainTypeName => object(stdClass) {
     *              Zh_tw => string
     *              En => string
     *          }
     *          TripLine => int
     *          WheelchairFlag => int
     *          PackageServiceFlag => int
     *          DiningFlag => int
     *          BikeFlag => int
     *          BreastFeedingFlag => int
     *          DailyFlag => int
     *          ServiceAddedFlag => int
     *          SuspendedFlag => int
     *          Note => object(stdClass) {
     *              Zh_tw => string
     *          }
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
     *          SuspendedFlag => int
     *      }
     *      UpdateTime => string
     *      VersionID => int
     * }
     */
    public function getTraTrainAndArrival()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://tdx.transportdata.tw/api/basic/v2/Rail/TRA/DailyTimetable/Today?%24top=30&%24format=JSON";
        return $this->curlGet($url, $accessToken);
    }

    /**
     * 利用 ORM Model 寫入台鐵車次資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraTrain()
    {
        $result = $this->getTraTrainAndArrival();
        $traTrainModel = new TraTrainModel();

        foreach ($result as $value) {
            $traTrainModel->save([
                'RT_id' => $value->DailyTrainInfo->TrainNo,
                'RT_departure_date' => $value->TrainDate
            ]);
        }

        return 1;
    }

    /**
     * 利用 ORM Model 寫入台鐵時刻表資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraArrival()
    {
        $result = $this->getTraTrainAndArrival();
        $traArrivalModel = new TraArrivalModel();

        foreach ($result as $value) {
            foreach ($value->StopTimes as $stopTime) {
                $traArrivalModel->save([
                    'RA_train_id' => $value->DailyTrainInfo->TrainNo,
                    'RA_station_id' => $stopTime->StationID,
                    'RA_arrival_time' => $stopTime->ArrivalTime,
                    'RA_direction' => $value->DailyTrainInfo->Direction
                ]);
            }
        }

        return 1;
    }

    /**
     * 利用 ORM Model 寫入台鐵車次與時刻表資料至 SQL 內。
     * 
     * @return boolean true | false
     */
    public function setTraTrainANdArrival()
    {
        $result = $this->getTraTrainAndArrival();
        $traTrainModel = new TraTrainModel();
        $traArrivalModel = new TraArrivalModel();

        foreach ($result as $value) {
            $traTrainModel->save([
                'RT_id' => $value->DailyTrainInfo->TrainNo,
                'RT_departure_date' => $value->TrainDate
            ]);

            foreach ($value->StopTimes as $stopTime) {
                $traArrivalModel->save([
                    'RA_train_id' => $value->DailyTrainInfo->TrainNo,
                    'RA_station_id' => 'TRA-' . $stopTime->StationID,
                    'RA_arrival_time' => $stopTime->ArrivalTime,
                    'RA_direction' => $value->DailyTrainInfo->Direction
                ]);
            }
        }

        return 1;
    }
}
