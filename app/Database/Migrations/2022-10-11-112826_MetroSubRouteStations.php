<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroSubRouteStations extends Migration
{
    public function up()
    {
        $fields = [
            "MSRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MSRS_sub_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MSRS_direction" => [
                "type" => "TINYINT"
            ],
            "MSRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MSRS_station_id", "MSRS_sub_route_id", "MSRS_direction"]);
        $this->forge->addForeignKey("MSRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MSRS_sub_route_id", "metro_sub_routes", "MSR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_sub_route_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_sub_route_stations", true, true);
    }
}
