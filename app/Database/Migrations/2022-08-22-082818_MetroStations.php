<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroStations extends Migration
{
    public function up()
    {
        /**
         * 新增捷運車站資料表
         */
        $fields = [
            "MS_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MS_name_TC" => [
                "type"       => "VARCHAR",
                "constraint" => 10
            ],
            "MS_name_EN" => [
                "type"       => "VARCHAR",
                "constraint" => 35
            ],
            "MS_city_id" => [
                "type"       => "VARCHAR",
                "constraint" => 3
            ],
            "MS_longitude" => [
                "type"       => "FLOAT"
            ],
            "MS_latitude" => [
                "type"       => "FLOAT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MS_id", "MS_city_id"]);
        $this->forge->addForeignKey("MS_city_id", "cities", "C_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_stations", true);
    }

    /**
     * 刪除捷運車站資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_stations", true, true);
    }
}
