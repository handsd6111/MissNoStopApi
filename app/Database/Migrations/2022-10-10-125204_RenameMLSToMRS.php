<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMLSToMRS extends Migration
{
    public function up()
    {
        $this->forge->renameTable("metro_line_stations", "metro_route_stations");
        $fields = [
            "MLS_station_id" => [
                "name"       => "MRS_station_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MLS_line_id" => [
                "name"       => "MRS_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MLS_sequence" => [
                "name"       => "MRS_sequence",
                "type"       => "SMALLINT"
            ],
        ];
        $this->forge->modifyColumn("metro_route_stations", $fields);
    }

    public function down()
    {
        $this->forge->renameTable("metro_route_stations", "metro_line_stations");
        $fields = [
            "MRS_station_id" => [
                "name"       => "MLS_station_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_route_id" => [
                "name"       => "MLS_line_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_sequence" => [
                "name"       => "MLS_sequence",
                "type"       => "SMALLINT"
            ],
        ];
        $this->forge->modifyColumn("metro_line_stations", $fields);
    }
}
