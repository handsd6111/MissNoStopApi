<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMDSubRouteId extends Migration
{
    public function up()
    {
        $fields = [
            "MD_route_id" => [
                "name"       => "MD_sub_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("metro_durations", $fields);
    }

    public function down()
    {
        $fields = [
            "MD_sub_route_id" => [
                "name"       => "MD_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("metro_durations", $fields);
    }
}
