<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMDStopTimeAgain extends Migration
{
    public function up()
    {
        $fields = [
            "MD_stop_time" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addColumn("metro_durations", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("metro_durations", "MD_stop_time");
    }
}
