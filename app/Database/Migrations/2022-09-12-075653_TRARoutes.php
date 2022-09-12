<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRARoutes extends Migration
{
    /**
     * 新增臺鐵路線資料表
     */
    public function up()
    {
        $fields = [
            "RR_id" => [
                "type" => "VARCHAR",
                "constraint" => 5
            ],
            "RR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "RR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("RR_id");
        $this->forge->createTable("TRA_routes", true);
    }

    public function down()
    {
        $this->forge->dropTable("TRA_routes", true, true);
    }
}
