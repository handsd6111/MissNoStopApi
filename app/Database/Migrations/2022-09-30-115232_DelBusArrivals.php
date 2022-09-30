<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelBusArrivals extends Migration
{
    /**
     * 刪除公車時刻表
     */
    public function up()
    {
        $this->forge->dropTable("bus_arrivals", true, true);
    }

    public function down()
    {
        $fields = [
            "BA_car_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BA_arrival_time" => [
                "type" => "TIME"
            ],
            "BA_direction" => [
                "type" => "TINYINT"
            ],
            "BA_arrives_today" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BA_car_id", "BA_station_id"]);
        $this->forge->addForeignKey("BA_car_id", "bus_cars", "BC_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("BA_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_arrivals");
    }
}
