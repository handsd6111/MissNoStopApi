<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameMRSToMLS extends Migration
{
    public function up()
    {
        $this->forge->renameTable("metro_route_stations", "metro_line_stations");
    }

    public function down()
    {
        $this->forge->renameTable("metro_line_stations", "metro_route_stations");
    }
}
