<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class THSRStations extends Migration
{
    /**
     * 新增高鐵站資料表
     */
    public function up()
    {
        $fields = [
            "HS_id" => [
                "type" => "VARCHAR",
                "constraint" => 5
            ],
            "HS_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "HS_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ],
            "HS_city_id" => [
                "type" => "VARCHAR",
                "constraint" => 3
            ],
            "HS_longitude" => [
                "type" => "FLOAT"
            ],
            "HS_latitude" => [
                "type" => "FLOAT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("HS_id");
        $this->forge->addForeignKey("HS_city_id", "cities", "C_id");
        $this->forge->createTable("THSR_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("THSR_stations", true, true);
    }
}
