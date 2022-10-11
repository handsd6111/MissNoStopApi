<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroArrivals extends Migration
{
    public function up()
    {
        $fields = [
            "MA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MA_sub_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MA_direction" => [
                "type" => "TINYINT"
            ],
            "MA_sequence" => [
                "type" => "SMALLINT"
            ],
            "MA_arrival_time" => [
                "type" => "TIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MA_station_id", "MA_sub_route_id", "MA_direction", "MA_sequence"]);
        $this->forge->addForeignKey("MA_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MA_sub_route_id", "metro_sub_routes", "MSR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_arrivals", true, true);
    }
}
