<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RetypeMDDuration extends Migration
{
    /**
     * 將 metro_durations 的 MD_duration 型別修正為 SmallInt
     */
    public function up()
    {
        $fields = [
            "MD_duration" => [
                "type" => "SMALLINT"
            ]
        ];
        $this->forge->modifyColumn("metro_durations", $fields);
    }

    public function down()
    {
        $fields = [
            "MD_duration" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->modifyColumn("metro_durations", $fields);
    }
}
