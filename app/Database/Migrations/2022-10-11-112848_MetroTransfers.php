<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetroTransfers extends Migration
{
    public function up()
    {
        $fields = [
            "MT_from_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MT_to_station_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ],
            "MT_transfer_time" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey(["MT_from_station_id", "MT_to_station_id"]);
        $this->forge->addForeignKey("MT_from_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->addForeignKey("MT_to_station_id", "metro_stations", "MS_id", "CASCADE", "CASCADE");
        $this->forge->createTable("metro_transfers", true);
    }

    public function down()
    {
        $this->forge->dropTable("metro_transfers", true, true);
    }
}
