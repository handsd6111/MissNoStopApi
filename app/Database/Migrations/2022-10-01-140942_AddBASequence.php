<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBASequence extends Migration
{
    public function up()
    {
        $fields = [
            "BA_sequence" => [
                "type" => "SMALLINT",
                "after" => "BA_direction"
            ]
        ];
        $this->forge->addColumn("bus_arrivals", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("bus_arrivals", "BA_sequence");
    }
}
