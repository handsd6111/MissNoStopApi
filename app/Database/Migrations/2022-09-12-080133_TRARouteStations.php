<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRARouteStations extends Migration
{
    /**
     * 新增臺鐵路線車站資料表
     */
    public function up()
    {
        $fields = [
            "RRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "RRS_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 5
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["RRS_station_id", "RRS_route_id"]);
        $this->forge->addForeignKey("RRS_station_id", "TRA_stations", "RS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("RRS_route_id", "TRA_routes", "RR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("TRA_route_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("TRA_route_stations", true, true);
    }
}
