<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusArrivals extends Migration
{
    public function up()
    {
        $fields = [
            "BA_trip_id" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BA_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BA_direction" => [
                "type" => "TINYINT"
            ],
            "BA_arrival_time" => [
                "type" => "TIME"
            ],
            "BA_departure_time" => [
                "type" => "TIME"
            ],
            "BA_arrives_today" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BA_trip_id", "BA_station_id", "BA_route_id", "BA_direction"]);
        $this->forge->addForeignKey("BA_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("BA_route_id", "bus_routes", "BR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_arrivals", true, true);
    }
}
