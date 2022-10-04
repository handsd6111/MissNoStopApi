<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelBusTrips extends Migration
{
    /**
     * 刪除公車車次資料表
     */
    public function up()
    {
        $this->forge->dropTable("bus_trips", true, true);
    }

    public function down()
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
}
