<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class THSRArrivals extends Migration
{
    /**
     * 新增高鐵時刻資料表
     */
    public function up()
    {
        $fields = [
            "HA_train_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ],
            "HA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 5
            ],
            "HA_arrival_time" => [
                "type" => "TIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["HA_train_id", "HA_station_id"]);
        $this->forge->addForeignKey("HA_train_id", "THSR_trains", "HT_id");
        $this->forge->addForeignKey("HA_station_id", "THSR_stations", "HS_id");
        $this->forge->createTable("THSR_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("THSR_arrivals");
    }
}
