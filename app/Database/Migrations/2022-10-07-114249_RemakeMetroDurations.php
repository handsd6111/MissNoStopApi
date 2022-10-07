<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeMetroDurations extends Migration
{
    public function up()
    {
        $this->forge->dropTable("metro_durations", true, true);
        $fields = [
            "MD_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MD_direction" => [
                "type" => "TINYINT"
            ],
            "MD_duration" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MD_station_id", "MD_direction"]);
        $this->forge->addForeignKey("MD_station_id", "metro_stations", "MS_id");
        $this->forge->createTable("metro_durations", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_durations", true, true);
        $fields = [
            "MD_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MD_end_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MD_duration" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("MD_station_id");
        $this->forge->addForeignKey("MD_station_id", "metro_stations", "MS_id");
        $this->forge->addForeignKey("MD_end_station_id", "metro_stations", "MS_id");
        $this->forge->createTable("metro_durations", true);
    }
}
