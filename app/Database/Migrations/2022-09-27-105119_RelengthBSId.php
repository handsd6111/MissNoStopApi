<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthBSId extends Migration
{
    /**
     * 加長 BS_id 的長度
     */
    public function up()
    {
        $fields = [
            "BS_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->modifyColumn("bus_stations", $fields);
    }

    public function down()
    {
        $fields = [
            "BS_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("bus_stations", $fields);
    }
}
