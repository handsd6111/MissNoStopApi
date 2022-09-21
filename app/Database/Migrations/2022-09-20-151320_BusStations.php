<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusStations extends Migration
{
    /**
     * 建立公車車站
     */
    public function up()
    {
        $fields = [
            "BS_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "BS_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "BS_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ],
            "BS_city_id" => [
                "type" => "VARCHAR",
                "constraint" => 5
            ],
            "BS_longitude" => [
                "type" => "FLOAT"
            ],
            "BS_latitude" => [
                "type" => "FLOAT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BS_id");
        $this->forge->addForeignKey("BS_city_id", "cities", "C_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_stations");
    }

    public function down()
    {
        $this->forge->dropTable("bus_stations", true, true);
    }
}
