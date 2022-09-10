<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthHSID extends Migration
{
    /**
     * 將 THSR_stations 資料表內 HS_id 的長度修正為 11
     */
    public function up()
    {
        $field = [
            "HS_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ]
        ];
        $this->forge->modifyColumn("THSR_stations", $field);
    }

    public function down()
    {
        $field = [
            "HS_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ]
        ];
        $this->forge->modifyColumn("THSR_stations", $field);
    }
}
