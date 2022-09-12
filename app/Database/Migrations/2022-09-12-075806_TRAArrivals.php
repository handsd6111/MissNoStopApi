<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRAArrivals extends Migration
{
    /**
     * 新增臺鐵時刻表資料表
     */
    public function up()
    {
        $fields = [
            "RA_train_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "RA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "RA_arrival_time" => [
                "type" => "TIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["RA_train_id", "RA_station_id"]);
        $this->forge->addForeignKey("RA_train_id", "TRA_trains", "RT_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("RA_station_id", "TRA_stations", "RS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("TRA_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("TRA_arrivals", true, true);
    }
}
