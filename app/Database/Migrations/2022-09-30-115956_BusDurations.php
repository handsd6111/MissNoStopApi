<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusDurations extends Migration
{
    /**
     * 新增公車站間運行時間
     */
    public function up()
    {
        $fields = [
            "BD_from_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BD_to_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BD_week" => [
                "type" => "TINYINT"
            ],
            "BD_hour" => [
                "type" => "TINYINT"
            ],
            "BD_duration" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BD_from_station_id", "BD_to_station_id", "BD_week", "BD_hour"]);
        $this->forge->addForeignKey("BD_from_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("BD_to_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_durations", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_durations", true, true);
    }
}
