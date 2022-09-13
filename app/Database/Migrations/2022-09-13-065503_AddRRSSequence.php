<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRRSSequence extends Migration
{
    // 新增 RRS_sequence
    public function up()
    {
        $fields = [
            "RRS_sequence" => [
                "type" => "SMALLINT"
            ]  
        ];
        $this->forge->addColumn("TRA_route_stations", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("TRA_route_stations", "RRS_sequence");
    }
}
