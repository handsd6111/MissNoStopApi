<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroDurations extends Migration
{
    /**
     * 新增捷運運行時間資料表
     */
    public function up()
    {
        $fields = [
            "MD_station_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MD_end_station_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MD_duration" => [
                "type"       => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MD_station_id", "MD_end_station_id"]);
        $this->forge->addForeignKey("MD_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MD_end_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_durations", true);
    }

    /**
     * 刪除捷運運行時間資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_durations", true, true);
    }
}
