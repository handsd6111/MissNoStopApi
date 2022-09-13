<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRADirection extends Migration
{
    // 新增 RA_direction
    public function up()
    {
        $fields = [
            "RA_direction" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addColumn("TRA_arrivals", $fields);
    }

    public function down()
    {
        $this->forge->dropColumn("TRA_arrivals", "RA_direction");
    }
}
