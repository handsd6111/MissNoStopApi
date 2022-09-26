<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemakeBusArrivals extends Migration
{
    /**
     * 修正公車時刻表
     */
    public function up()
    {
        $fields = [
            "BA_monday" => [
                "name" => "BA_arrives_today",
                "type" => "TINYINT"
            ]
        ];
        $this->forge->modifyColumn("bus_arrivals", $fields);
        $columns = [
            "BA_tuesday",
            "BA_wednesday",
            "BA_thursday",
            "BA_friday",
            "BA_saturday",
            "BA_sunday"
        ];
        $this->forge->dropColumn("bus_arrivals", $columns);
    }

    public function down()
    {
        $columns = [
            "BA_tuesday" => [
                "type" => "TINYINT"
            ],
            "BA_wednesday" => [
                "type" => "TINYINT"
            ],
            "BA_thursday" => [
                "type" => "TINYINT"
            ],
            "BA_friday" => [
                "type" => "TINYINT"
            ],
            "BA_saturday" => [
                "type" => "TINYINT"
            ],
            "BA_sunday" => [
                "type" => "TINYINT"
            ]
        ];
        $this->forge->addColumn("bus_arrivals", $columns);
        $fields = [
            "BA_arrives_today" => [
                "name" => "BA_monday",
                "type" => "TINYINT"
            ]
        ];
        $this->forge->modifyColumn("bus_arrivals", $fields);
    }
}
