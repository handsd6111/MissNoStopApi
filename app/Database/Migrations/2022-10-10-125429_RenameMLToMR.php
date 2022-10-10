<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMLToMR extends Migration
{
    public function up()
    {
        $this->forge->renameTable("metro_lines", "metro_routes");
        $fields = [
            "ML_id" => [
                "name"       => "MR_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "ML_name_TC" => [
                "name"       => "MR_name_TC",
                "type"       => "VARCHAR",
                "constraint" => 20
            ],
            "ML_name_EN" => [
                "name"       => "MR_name_EN",
                "type"       => "VARCHAR",
                "constraint" => 40
            ],
            "ML_system_id" => [
                "name"       => "MR_system_id",
                "type"       => "VARCHAR",
                "constraint" => 4
            ],
        ];
        $this->forge->modifyColumn("metro_routes", $fields);
    }

    public function down()
    {
        $this->forge->renameTable("metro_routes", "metro_lines");
        $fields = [
            "MR_id" => [
                "name"       => "ML_id",
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MR_name_TC" => [
                "name"       => "ML_name_TC",
                "type"       => "VARCHAR",
                "constraint" => 20
            ],
            "MR_name_EN" => [
                "name"       => "ML_name_EN",
                "type"       => "VARCHAR",
                "constraint" => 40
            ],
            "MR_system_id" => [
                "name"       => "ML_system_id",
                "type"       => "VARCHAR",
                "constraint" => 4
            ],
        ];
        $this->forge->modifyColumn("metro_lines", $fields);
    }
}
