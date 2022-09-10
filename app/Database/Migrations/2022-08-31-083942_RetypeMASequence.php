<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RetypeMASequence extends Migration
{
    /**
     * 修正 MA_sequence 型別為 SmallInt
     */
    public function up()
    {
        $fields = [
            "MA_sequence" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }

    public function down()
    {
        $fields = [
            "MA_sequence" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }
}
