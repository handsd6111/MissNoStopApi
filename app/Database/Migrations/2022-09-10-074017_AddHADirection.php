<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHADirection extends Migration
{
    /**
     * 於 THSR_arrivals 新增屬性 HA_direction
     */
    public function up()
    {
        $fields = [
            "HA_direction" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addColumn("THSR_arrivals", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("THSR_arrivals", "HA_direction");
    }
}
