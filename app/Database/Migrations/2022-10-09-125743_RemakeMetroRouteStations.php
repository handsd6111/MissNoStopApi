<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeMetroRouteStations extends Migration
{
    public function up()
    {
        $this->forge->dropTable("metro_route_stations", true, true);
        $fields = [
            "MRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_line_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MRS_station_id", "MRS_line_id"]);
        $this->forge->addForeignKey("MRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MRS_line_id", "metro_lines", "ML_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_route_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_route_stations", true, true);
        $fields = [
            "MRS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MRS_station_id", "MRS_route_id"]);
        $this->forge->addForeignKey("MRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MRS_route_id", "metro_routes", "MR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_route_stations", true);
    }
}
