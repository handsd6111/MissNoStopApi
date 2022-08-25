<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroRoutes extends Migration
{
    /**
     * 新增捷運路線資料表
     */
    public function up()
    {
        $fields = [
            "MR_id" => [
                "type"       => "VARCHAR",
                "constraint" => 12
            ],
            "MR_name_TC" => [
                "type"       => "VARCHAR",
                "constraint" => 10
            ],
            "MR_name_EN" => [
                "type"       => "VARCHAR",
                "constraint" => 35
            ],
            "MR_system_id" => [
                "type"       => "VARCHAR",
                "constraint" => 4
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("MR_id");
        $this->forge->addForeignKey("MR_system_id", "metro_systems", "MST_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_routes", true);
    }

    /**
     * 刪除捷運路線資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_routes", true, true);
    }
}
