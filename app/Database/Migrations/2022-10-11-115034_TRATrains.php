<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRATrains extends Migration
{
    public function up()
    {
        $fields = [
            "RT_id" => [
                "type" => "VARCHAR",
                "constraint" => 11
            ],
            "RT_departure_date" => [
                "type" => "DATETIME"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("RT_id");
        $this->forge->createTable("TRA_trains", true);
    }

    public function down()
    {
        $this->forge->dropTable("TRA_trains", true, true);
    }
}
