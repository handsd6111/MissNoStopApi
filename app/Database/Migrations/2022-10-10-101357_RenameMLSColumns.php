<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMLSColumns extends Migration
{
    public function up()
    {
        $fields = [
            "MRS_station_id" => [
                "name" => "MLS_station_id",
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_line_id" => [
                "name" => "MLS_line_id",
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MRS_sequence" => [
                "name" => "MLS_sequence",
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->modifyColumn("metro_line_stations", $fields);
    }

    public function down()
    {
        $fields = [
            "MLS_station_id" => [
                "name" => "MRS_station_id",
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MLS_line_id" => [
                "name" => "MRS_line_id",
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MLS_sequence" => [
                "name" => "MRS_sequence",
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->modifyColumn("metro_line_stations", $fields);
    }
}
