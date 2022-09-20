<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusCar extends Migration
{
    /**
     * 新增公車車輛
     */
    public function up()
    {
        $fields = [
            "BC_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BC_id");
        $this->forge->createTable("bus_cars", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_cars", true, true);
    }
}
