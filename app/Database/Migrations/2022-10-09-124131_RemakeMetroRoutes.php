<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeMetroRoutes extends Migration
{
    public function up()
    {
        $this->forge->dropTable("metro_routes", true, true);
        $fields = [
        "MR_id" => [
            "type" => "VARCHAR",
            "constraint" => 12
        ],
        "MR_name_TC" => [
            "type" => "VARCHAR",
            "constraint" => 30
        ],
        "MR_name_EN" => [
            "type" => "VARCHAR",
            "constraint" => 50
        ],
        "MR_line_id" => [
            "type" => "VARCHAR",
            "constraint" => 12
        ]
    ];
    $this->forge->addField($fields);
    $this->forge->addPrimaryKey("MR_id");
    $this->forge->addForeignKey("MR_line_id", "metro_lines", "ML_id", "CASCADE", "CASCADE");
    $this->forge->createTable("metro_routes", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_routes", true, true);
        $fields = [
        "MR_id" => [
            "type" => "VARCHAR",
            "constraint" => 12
        ],
        "MR_name_TC" => [
            "type" => "VARCHAR",
            "constraint" => 20
        ],
        "MR_name_EN" => [
            "type" => "VARCHAR",
            "constraint" => 40
        ],
        "MR_system_id" => [
            "type" => "VARCHAR",
            "constraint" => 4
        ]
    ];
    $this->forge->addField($fields);
    $this->forge->addPrimaryKey("MR_id");
    $this->forge->addForeignKey("MR_system_id", "metro_systems", "MST_id", "CASCADE", "CASCADE");
    $this->forge->createTable("metro_routes", true);
    }
}
