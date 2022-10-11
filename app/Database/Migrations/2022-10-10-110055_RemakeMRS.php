<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeMRS extends Migration
{
    public function up()
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
            "MRS_direction" => [
                "type" => "TINYINT"
            ],
            "MRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MRS_station_id", "MRS_route_id", "MRS_direction"]);
        $this->forge->addForeignKey("MRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MRS_route_id", "metro_routes", "MR_id", "CASCADE", "CASCADE");
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
        $this->forge->addPrimaryKey(["MRS_station_id", "MRS_route_id", "MRS_direction"]);
        $this->forge->addForeignKey("MRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MRS_route_id", "metro_routes", "MR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_route_stations", true);
    }
}
