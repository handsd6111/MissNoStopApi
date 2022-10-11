<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroLines extends Migration
{
    public function up()
    {
        $fields = [
            "ML_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "ML_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 20
            ],
            "ML_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 40
            ],
            "ML_system_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("ML_id");
        $this->forge->addForeignKey("ML_system_id", "metro_systems", "MST_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_lines", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_lines", true, true);
    }
}
