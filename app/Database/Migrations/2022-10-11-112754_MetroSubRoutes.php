<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroSubRoutes extends Migration
{
    public function up()
    {
        $fields = [
            "MSR_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MSR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 30
            ],
            "MSR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 50
            ],
            "MSR_route_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("MSR_id");
        $this->forge->addForeignKey("MSR_route_id", "metro_routes", "MR_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_sub_routes", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_sub_routes", true, true);
    }
}
