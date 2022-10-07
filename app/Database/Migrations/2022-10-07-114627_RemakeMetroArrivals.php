<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeMetroArrivals extends Migration
{
    public function up()
    {
        $this->forge->dropTable("metro_arrivals", true, true);
        $fields = [
            "MA_station_id" => [
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
        $this->forge->addPrimaryKey(["MA_station_id", "MA_direction", "MA_sequence"]);
        $this->forge->addForeignKey("MA_station_id", "metro_stations", "MS_id");
        $this->forge->createTable("metro_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_arrivals", true, true);
        $fields = [
            "MA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MA_end_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MA_sequence" => [
                "type" => "SMALLINT"
            ],
            "MA_arrival_time" => [
                "type" => "TIME"
            ],
            "MA_departure_time" => [
                "type" => "TIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MA_station_id", "MA_end_station_id", "MA_sequence"]);
        $this->forge->addForeignKey("MA_station_id", "metro_stations", "MS_id");
        $this->forge->addForeignKey("MA_end_station_id", "metro_stations", "MS_id");
        $this->forge->createTable("metro_arrivals", true);
    }
}
