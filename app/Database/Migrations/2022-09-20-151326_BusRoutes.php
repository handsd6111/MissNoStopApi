<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusRoutes extends Migration
{
    /**
     * 建立公車路線
     */
    public function up()
    {
        $fields = [
            "BR_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
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
    }
}
