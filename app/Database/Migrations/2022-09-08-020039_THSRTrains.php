<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class THSRTrains extends Migration
{
    /**
     * 新增高鐵列車資料表
     */
    public function up()
    {
        $fields = [
            "HT_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ],
            "HT_departure_time" => [
                "type" => "DATETIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("HT_id");
        $this->forge->createTable("THSR_trains", true);
    }

    public function down()
    {
        $this->forge->dropTable("THSR_trains", true, true);
    }
}
