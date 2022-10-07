<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelengthMRNames extends Migration
{
    public function up()
    {
        $fields = [
            "MR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 20
            ],
            "MR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 45
            ]
        ];
        $this->forge->modifyColumn("metro_routes", $fields);
    }

    public function down()
    {
        $fields = [
            "MR_name_TC" => [
                "type" => "VARCHAR",
                "constraint" => 10
            ],
            "MR_name_EN" => [
                "type" => "VARCHAR",
                "constraint" => 35
            ]
        ];
        $this->forge->modifyColumn("metro_routes", $fields);
    }
}
