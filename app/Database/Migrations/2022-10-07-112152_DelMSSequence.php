<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelMSSequence extends Migration
{
    public function up()
    {
        $this->forge->dropColumn("metro_stations", "MS_sequence");
    }

    public function down()
    {
        $fields = [
            "MS_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addColumn("metro_stations", $fields);
    }
}
