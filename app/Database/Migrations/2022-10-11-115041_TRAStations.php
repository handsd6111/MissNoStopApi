<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRAStations extends Migration
{
    public function up()
    {
        $fields = [
            "RS_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "RS_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "RS_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ],
            "RS_city_id" => [
                "type" => "VARCHAR",
                "constraint" => 3
            ],
            "RS_longitude" => [
                "type" => "FLOAT"
            ],
            "RS_latitude" => [
                "type" => "FLOAT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("RS_id");
        $this->forge->addForeignKey("RS_city_id", "cities", "C_id", "CASCADE", "CASCADE");
        $this->forge->createTable("TRA_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("TRA_stations", true, true);
    }
}
