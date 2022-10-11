<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroDurations extends Migration
{
    public function up()
    {
        $fields = [
            "MD_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MD_sub_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MD_direction" => [
                "type" => "TINYINT"
            ],
            "MD_duration" => [
                "type" => "SMALLINT"
            ],
            "MD_stop_time" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MD_station_id", "MD_sub_route_id", "MD_direction"]);
        $this->forge->addForeignKey("MD_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MD_sub_route_id", "metro_sub_routes", "MSR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_durations", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_durations", true, true);
    }
}
