<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelBRCityId extends Migration
{
    public function up()
    {
        $this->forge->dropTable("bus_routes", true, true);
        $fields = [
            "BR_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "BR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BR_id");
        $this->forge->createTable("bus_routes", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_routes", true, true);
        $fields = [
            "BR_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ],
            "BR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "BR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ],
            "BR_city_id" => [
                "type" => "VARCHAR",
                "constraint" => 3
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BR_id");
        $this->forge->addForeignKey("BR_city_id", "cities", "C_id", "CASCADE", "CASCADE");
        $this->forge->createTable("bus_routes", true);
    }
}
