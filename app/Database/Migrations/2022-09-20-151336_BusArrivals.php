<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusArrivals extends Migration
{
    /**
     * 建立公車時刻表
     */
    public function up()
    {
        $fields = [
            "BA_car_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BA_arrival_time" => [
                "type" => "TIME"
            ],
            "BA_direction" => [
                "type" => "TINYINT"
            ],
            "BA_monday" => [
                "type" => "TINYINT"
            ],
            "BA_tuesday" => [
                "type" => "TINYINT"
            ],
            "BA_wednesday" => [
                "type" => "TINYINT"
            ],
            "BA_thursday" => [
                "type" => "TINYINT"
            ],
            "BA_friday" => [
                "type" => "TINYINT"
            ],
            "BA_saturday" => [
                "type" => "TINYINT"
            ],
            "BA_sunday" => [
                "type" => "TINYINT"
            ],
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BA_car_id", "BA_station_id"]);
        $this->forge->addForeignKey("BA_car_id", "bus_cars", "BC_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("BA_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_arrivals");
    }

    public function down()
    {
        $this->forge->dropTable("bus_arrivals", true, true);
    }
}
