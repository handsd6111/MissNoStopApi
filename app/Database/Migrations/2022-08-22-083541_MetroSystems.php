<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroSystems extends Migration
{
    /**
     * 新增捷運系統資料表
     */
    public function up()
    {
        $fields = [
            "MST_id" => [
                "type"       => "VARCHAR",
                "constraint" => 4
            ],
            "MST_name_TC" => [
                "type"       => "VARCHAR",
                "constraint" => 15
            ],
            "MST_name_EN" => [
                "type"       => "VARCHAR",
                "constraint" => 40
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("MST_id");
        $this->forge->createTable("metro_systems", true);
    }

    /**
     * 刪除捷運系統資料表
     */
    public function down()
    {
        $this->forge->dropTable("metro_systems", true, true);
    }
}
