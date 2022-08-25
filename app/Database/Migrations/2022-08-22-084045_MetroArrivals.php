<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroArrivals extends Migration
{
    /**
     * 新增捷運到站離站時刻資料表
     */
    public function up()
    {
        $fields = [
            "MA_station_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MA_end_station_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MA_sequence" => [
                "type"       => "TINYINT"
            ],
            "MA_arrival_time" => [
                "type"       => "TIME" 
            ],
            "MA_departure_time" => [
                "type"       => "TIME" 
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MA_station_id", "MA_end_station_id", "MA_sequence"]);
        $this->forge->addForeignKey("MA_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MA_end_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_arrivals", true);
    }

    /**
     * 刪除捷運到站離站時刻資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_arrivals", true, true);
    }
}
