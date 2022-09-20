<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusRouteStations extends Migration
{
    /**
     * 建立公車路線車站
     */
    public function up()
    {
        $fields = [
            "BRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BRS_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BRS_station_id", "BRS_route_id"]);
        $this->forge->addForeignKey("BRS_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("BRS_route_id", "bus_routes", "BR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_route_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_route_stations", true, true);
    }
}
