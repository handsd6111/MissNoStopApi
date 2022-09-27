<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthBRId extends Migration
{
    /**
     * 加長 BR_id 的長度
     */
    public function up()
    {
        $fields = [
            "BR_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->modifyColumn("bus_routes", $fields);
    }

    public function down()
    {
        $fields = [
            "BR_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->modifyColumn("bus_routes", $fields);
    }
}
