<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthBAStationId extends Migration
{
    /**
     * 加長 BA_station_id 的長度
     */
    public function up()
    {
        $fields = [
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->modifyColumn("bus_arrivals", $fields);
    }

    public function down()
    {
        $fields = [
            "BA_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("bus_arrivals", $fields);
    }
}
