<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMRSSequence extends Migration
{
    public function up()
    {
        $fields = [
            "MRS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addColumn("metro_route_stations", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("metro_route_stations", "MRS_sequence");
    }
}
