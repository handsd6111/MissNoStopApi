<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusStations extends Migration
{
    public function up()
    {
        $fields = [
            "BS_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BS_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 20
            ],
            "BS_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 45
            ],
            "BS_city_id" => [
                "type" => "VARCHAR",
                "constraint" => 3
            ],
            "BS_longitude" => [
                "type" => "FLOAT"
            ],
            "BS_latitude" => [
                "type" => "FLOAT"
            ],
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BS_id");
        $this->forge->addForeignKey("BS_city_id", "cities", "C_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_stations", true, true);
    }
}
