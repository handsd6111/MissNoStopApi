<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMASubRouteId extends Migration
{
    public function up()
    {
        $fields = [
            "MA_route_id" => [
                "name"       => "MA_sub_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }

    public function down()
    {
        $fields = [
            "MA_sub_route_id" => [
                "name"       => "MA_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }
}
