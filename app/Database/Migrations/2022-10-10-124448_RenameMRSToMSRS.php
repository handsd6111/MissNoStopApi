<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMRSToMSRS extends Migration
{
    public function up()
    {
        $this->forge->renameTable("metro_route_stations", "metro_sub_route_stations");
        $fields = [
            "MRS_station_id" => [
                "name"       => "MSRS_station_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_route_id" => [
                "name"       => "MSRS_sub_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_direction" => [
                "name"       => "MSRS_direction",
                "type"       => "TINYINT"
            ],
            "MRS_sequence" => [
                "name"       => "MSRS_sequence",
                "type"       => "SMALLINT"
            ],
        ];
        $this->forge->modifyColumn("metro_sub_route_stations", $fields);
    }

    public function down()
    {
        $this->forge->renameTable("metro_sub_route_stations", "metro_route_stations");
        $fields = [
            "MSRS_station_id" => [
                "name"       => "MRS_station_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MSRS_sub_route_id" => [
                "name"       => "MRS_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MSRS_direction" => [
                "name"       => "MRS_direction",
                "type"       => "TINYINT"
            ],
            "MSRS_sequence" => [
                "name"       => "MRS_sequence",
                "type"       => "SMALLINT"
            ],
        ];
        $this->forge->modifyColumn("metro_route_stations", $fields);
    }
}
