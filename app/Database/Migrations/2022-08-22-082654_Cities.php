<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Cities extends Migration
{
    /**
     * 新增縣市資料表
     */
    public function up()
    {
        $fields = [
            "C_id" => [
                "type"       => "VARCHAR",
                "constraint" => 3
            ],
            "C_name_TC" => [
                "type"       => "VARCHAR",
                "constraint" => 3
            ],
            "C_name_EN" => [
                "type"       => "VARCHAR",
                "constraint" => 20
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("C_id");
        $this->forge->createTable("cities", true);
    }

    /**
     * 刪除縣市資料表
     */
    public function down()
    {
        $this->forge->dropTable("cities", true, true);
    }
}
