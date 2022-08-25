<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroRouteStations extends Migration
{
    /**
     * 新增捷運路線車站資料表
     */
    public function up()
    {
        $fields = [
            "MRS_station_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_route_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MRS_station_id", "MRS_route_id"]);
        $this->forge->addForeignKey("MRS_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MRS_route_id", "metro_routes", "MR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_route_stations", true);
    }

    /**
     * 刪除捷運路線車站資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_route_stations", true, true);
    }
}
