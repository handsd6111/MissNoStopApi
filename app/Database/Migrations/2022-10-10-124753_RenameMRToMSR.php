<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMRToMSR extends Migration
{
    public function up()
    {
        $this->forge->renameTable("metro_routes", "metro_sub_routes");
        $fields = [
            "MR_id" => [
                "name"       => "MSR_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MR_name_TC" => [
                "name"       => "MSR_name_TC",
                "type"       => "VARCHAR",
                "constraint" => 30
            ],
            "MR_name_EN" => [
                "name"       => "MSR_name_EN",
                "type"       => "VARCHAR",
                "constraint" => 50
            ],
            "MR_line_id" => [
                "name"       => "MSR_route_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
        ];
        $this->forge->modifyColumn("metro_sub_routes", $fields);
    }

    public function down()
    {
        $this->forge->renameTable("metro_sub_routes", "metro_routes");
        $fields = [
            "MSR_id" => [
                "name"       => "MR_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MSR_name_TC" => [
                "name"       => "MR_name_TC",
                "type"       => "VARCHAR",
                "constraint" => 30
            ],
            "MSR_name_EN" => [
                "name"       => "MR_name_EN",
                "type"       => "VARCHAR",
                "constraint" => 50
            ],
            "MSR_route_id" => [
                "name"       => "MR_line_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
        ];
        $this->forge->modifyColumn("metro_routes", $fields);
    }
}
