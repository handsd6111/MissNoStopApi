<?php

namespace App\Models;

use Exception;

class FakeModel
{
    /**
     * 取得所有捷運系統資料
     * @return array 假捷運系統資料
     */
    function get_systems()
    {
        return [
            [
                "MST_id" => "TRTC",
                "MST_name_TC" => "臺北大眾捷運股份有限公司",
                "MST_name_EN" => "Taipei Rapid Transit Corporation"
            ],
            [
                "MST_id" => "TYMC",
                "MST_name_TC" => "桃園大眾捷運股份有限公司",
                "MST_name_EN" => "Taoyuan Metro Corporation"
            ],
            [
                "MST_id" => "KRTC",
                "MST_name_TC" => "高雄捷運股份有限公司",
                "MST_name_EN" => "Kaohsiung Rapid Transit Corporation"
            ]
        ];
    }

    /**
     * 取得指定捷運系統的所有路線假資料
     * @param string $systemId 捷運系統代碼
     * @return mixed 假路線資料
     */
    function get_routes($systemId)
    {
        return [
            "TRTC" => [
                [
                    "MR_id" => "TRTC-BL",
                    "MR_name_TC"   => "板南線",
                    "MR_name_EN"   => "Bannan Line"
                ],
                [
                    "MR_id" => "TRTC-BR",
                    "MR_name_TC" => "文湖線",
                    "MR_name_EN" => "Wenhu Line"
                ],
                [
                    "MR_id" => "TRTC-G",
                    "MR_name_TC" => "松山新店線",
                    "MR_name_EN" => "Songshan-Xindian Line"
                ],
                [
                    "MR_id" => "TRTC-O",
                    "MR_name_TC" => "中和新蘆線",
                    "MR_name_EN" => "Zhonghe-Xinlu Line"
                ],
                [
                    "MR_id" => "TRTC-R",
                    "MR_name_TC" => "淡水信義線",
                    "MR_name_EN" => "Tamsui-Xinyi Line"
                ],
                [
                    "MR_id" => "TRTC-Y",
                    "MR_name_TC" => "環狀線",
                    "MR_name_EN" => "Circular Line"
                ]
            ],
            "TYMC" => [
                [
                    "MR_id" => "TYMC-A",
                    "MR_name_TC" => "桃園機場捷運線",
                    "MR_name_EN" => "Airport MRT Line"
                ]
            ],
            "KRTC" => [
                [
                    "MR_id" => "KRTC-O",
                    "MR_name_TC" => "橘線",
                    "MR_name_EN" => "Orange Line"
                ],
                [
                    "MR_id" => "KRTC-R",
                    "MR_name_TC" => "紅線",
                    "MR_name_EN" => "Red Line"
                ]
            ]
        ][$systemId];
    }

    /**
     * 取得指定捷運系統及路線上所有車站的假資料
     * @param string $systemId 捷運系統代碼
     * @param string $routeId 路線代碼
     * @return mixed 車站假資料
     */
    function get_stations($systemId, $routeId)
    {
        return [
            [
                "MS_id"        => "TRTC-BL01",
                "MS_name_TC"   => "頂埔",
                "MS_name_EN"   => "Dingpu",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.4205,
                "MS_latitude"  => 24.96012,
            ],
            [
                "MS_id"        => "TRTC-BL02",
                "MS_name_TC"   => "永寧",
                "MS_name_EN"   => "Yongning",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.43613,
                "MS_latitude"  => 24.96682,
            ],
            [
                "MS_id"        => "TRTC-BL03",
                "MS_name_TC"   => "土城",
                "MS_name_EN"   => "Tucheng",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.44432,
                "MS_latitude"  => 24.97313,
            ],
            [
                "MS_id"        => "TRTC-BL04",
                "MS_name_TC"   => "海山",
                "MS_name_EN"   => "Haishan",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.44873,
                "MS_latitude"  => 24.985305,
            ],
            [
                "MS_id"        => "TRTC-BL05",
                "MS_name_TC"   => "亞東醫院",
                "MS_name_EN"   => "Far Eastern Hospital",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.452465,
                "MS_latitude"  => 24.99828,
            ],
            [
                "MS_id"        => "TRTC-BL06",
                "MS_name_TC"   => "府中",
                "MS_name_EN"   => "Fuzhong",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.459276,
                "MS_latitude"  => 25.008465,
            ],
            [
                "MS_id"        => "TRTC-BL07",
                "MS_name_TC"   => "板橋",
                "MS_name_EN"   => "Banqiao",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.462305,
                "MS_latitude"  => 25.013825,
            ],
            [
                "MS_id"        => "TRTC-BL08",
                "MS_name_TC"   => "新埔",
                "MS_name_EN"   => "Xinpu",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.468055,
                "MS_latitude"  => 25.02327,
            ],
            [
                "MS_id"        => "TRTC-BL09",
                "MS_name_TC"   => "江子翠",
                "MS_name_EN"   => "Jiangzicui",
                "MS_city_id"   => "新北市",
                "MS_longitude" => 121.47257,
                "MS_latitude"  => 25.030265,
            ],
        ];
    }
    /**
     * 取得指定車站及終點站方向的時刻表假資料
     * @param string $stationId 車站代碼
     * @param string $endStationId 終點車站代碼
     * @return mixed 時刻表假資料
     */
    function get_arrivals($stationId, $endStationId)
    {
        return [
            [
                "MA_sequence" => 1,
                "MA_arrival_time" => "06:00",
                "MA_departure_time" => "06:01"
            ],
            [
                "MA_sequence" => 2,
                "MA_arrival_time" => "07:00",
                "MA_departure_time" => "07:01"
            ],
            [
                "MA_sequence" => 3,
                "MA_arrival_time" => "08:00",
                "MA_departure_time" => "08:01"
            ],
            [
                "MA_sequence" => 4,
                "MA_arrival_time" => "09:00",
                "MA_departure_time" => "09:01"
            ],
            [
                "MA_sequence" => 5,
                "MA_arrival_time" => "10:00",
                "MA_departure_time" => "10:01"
            ],
            [
                "MA_sequence" => 6,
                "MA_arrival_time" => "11:00",
                "MA_departure_time" => "11:01"
            ],
            [
                "MA_sequence" => 7,
                "MA_arrival_time" => "12:00",
                "MA_departure_time" => "12:01"
            ],
            [
                "MA_sequence" => 8,
                "MA_arrival_time" => "13:00",
                "MA_departure_time" => "13:01"
            ],
            [
                "MA_sequence" => 9,
                "MA_arrival_time" => "14:00",
                "MA_departure_time" => "14:01"
            ],
            [
                "MA_sequence" => 10,
                "MA_arrival_time" => "15:00",
                "MA_departure_time" => "15:01"
            ],
            [
                "MA_sequence" => 11,
                "MA_arrival_time" => "16:00",
                "MA_departure_time" => "16:01"
            ],
            [
                "MA_sequence" => 12,
                "MA_arrival_time" => "17:00",
                "MA_departure_time" => "17:01"
            ],
            [
                "MA_sequence" => 13,
                "MA_arrival_time" => "18:00",
                "MA_departure_time" => "18:01"
            ],
            [
                "MA_sequence" => 14,
                "MA_arrival_time" => "19:00",
                "MA_departure_time" => "19:01"
            ],
            [
                "MA_sequence" => 15,
                "MA_arrival_time" => "20:00",
                "MA_departure_time" => "20:01"
            ],
            [
                "MA_sequence" => 16,
                "MA_arrival_time" => "21:00",
                "MA_departure_time" => "21:01"
            ],
            [
                "MA_sequence" => 17,
                "MA_arrival_time" => "22:00",
                "MA_departure_time" => "22:01"
            ],
            [
                "MA_sequence" => 18,
                "MA_arrival_time" => "23:00",
                "MA_departure_time" => "23:01"
            ]
        ];
    }
}
