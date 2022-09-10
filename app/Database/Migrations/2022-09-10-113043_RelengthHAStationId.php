<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthHAStationId extends Migration
{
    /**
     * 修正 HA_station_id 的長度為 11
     */
    public function up()
    {
        $field = [
            "HA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ]
        ];
        $this->forge->modifyColumn("THSR_arrivals", $field);
    }

    public function down()
    {
        $field = [
            "HA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 4
            ]
        ];
        $this->forge->modifyColumn("THSR_arrivals", $field);
    }
}
