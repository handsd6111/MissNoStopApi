<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BusTrips extends Migration
{
    public function up()
    {
        $fields = [
            "BT_id" => [
                "type" => "VARCHAR",
                "constraint" => 17
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BT_id");
        $this->forge->createTable("bus_trips", true);
    }

    public function down()
    {
        $this->forge->dropTable("bus_trips", true, true);
    }
}
