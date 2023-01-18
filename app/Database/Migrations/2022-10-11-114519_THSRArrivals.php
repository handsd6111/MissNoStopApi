<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class THSRArrivals extends Migration
{
    public function up()
    {
        $fields = [
            "HA_train_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ],
            "HA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "HA_arrival_time" => [
                "type" => "TIME"
            ],
            "HA_direction" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["HA_train_id", "HA_station_id"]);
        $this->forge->addForeignKey("HA_train_id", "THSR_trains", "HT_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("HA_station_id", "THSR_stations", "HS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("THSR_arrivals", true);
    }

    public function down()
    {
        $this->forge->dropTable("THSR_arrivals", true, true);
    }
}
