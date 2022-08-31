<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMAArrivalTime extends Migration
{
    /**
     * 將 metro_arrivals 資料表中的屬性 MA_arrival_time 更名為 MA_remain_time
     */
    public function up()
    {
        $fields = [
            "MA_arrival_time" => [
                "name" => "MA_remain_time",
                "type" => "TIME",
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }

    public function down()
    {
        $fields = [
            "MA_remain_time" => [
                "name" => "MA_arrival_time",
                "type" => "TIME",
            ]
        ];
        $this->forge->modifyColumn("metro_arrivals", $fields);
    }
}
