<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMSSequence extends Migration
{
    public function up()
    {
        $fields = [
            "MS_sequence" => [
                "type"       => "VARCHAR",
                "constraint" => "SMALLINT",
                "after"      => "MS_name_EN"
            ]
        ];
        $this->forge->addColumn("metro_stations", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("metro_stations", "MS_sequence");
    }
}
