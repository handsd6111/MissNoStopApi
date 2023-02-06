<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRAArrivals extends Migration
{
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
            ],
            "RA_departure_time" => [
                "type" => "TIME"
            ],
            "RA_direction" => [
                "type" => "TINYINT"
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
