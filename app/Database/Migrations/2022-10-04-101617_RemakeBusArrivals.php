<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeBusArrivals extends Migration
{
    public function up()
    {
        $this->forge->dropTable("bus_arrivals", true, true);
        $fields = [
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BA_direction" => [
                "type" => "TINYINT"
            ],
            "BA_arrival_time" => [
                "type" => "TIME"
            ],
            "BA_arrives_today" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BA_station_id", "BA_direction", "BA_arrival_time"]);
        $this->forge->addForeignKey("BA_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_arrivals", true, true);
        $fields = [
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BA_direction" => [
                "type" => "TINYINT"
            ],
            "BA_arrival_time" => [
                "type" => "TIME"
            ],
            "BA_arrives_today" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BA_station_id", "BA_direction"]);
        $this->forge->addForeignKey("BA_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_arrivals", true);
    }
}
