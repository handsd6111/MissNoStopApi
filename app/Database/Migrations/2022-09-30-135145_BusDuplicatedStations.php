<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusDuplicatedStations extends Migration
{
    /**
     * 新增公車重複車站
     */
    public function up()
    {
        $fields = [
            "BDS_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BDS_duplicated_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["BDS_station_id", "BDS_duplicated_id"]);
        $this->forge->addForeignKey("BDS_station_id", "bus_stations", "BS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_duplicated_stations", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_duplicated_stations", true, true);
    }
}
