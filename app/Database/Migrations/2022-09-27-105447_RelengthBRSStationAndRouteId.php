<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthBRSStationAndRouteId extends Migration
{
    /**
     * 加長 BRS_station_id 及 BRS_route_id 的長度
     */
    public function up()
    {
        $fields = [
            "BRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BRS_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->modifyColumn("bus_route_stations", $fields);
    }

    public function down()
    {
        $fields = [
            "BRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BRS_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("bus_route_stations", $fields);
    }
}
