<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelBD extends Migration
{
    /**
     * 刪除公車站間運行時間資料表
     */
    public function up()
    {
        $this->forge->dropTable("bus_durations", true, true);
    }

    public function down()
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
}
