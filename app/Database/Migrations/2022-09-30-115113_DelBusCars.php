<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DelBusCars extends Migration
{
    /**
     * 刪除公車車次
     */
    public function up()
    {
        $this->forge->dropTable("bus_cars", true, true);
    }

    public function down()
    {
        $fields = [
            "BC_id" => [
                "type" => "VARCHAR",
                "constraint" => 12
            ]
        ];
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey("BC_id");
        $this->forge->createTable("bus_cars", true);
    }
}
