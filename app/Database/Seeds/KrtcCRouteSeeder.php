<?php

namespace App\Database\Seeds;

use App\Models\ORM\MetroRouteModel;
use CodeIgniter\Database\Seeder;

class KrtcCRouteSeeder extends Seeder
{
    public function run()
    {
        $krtcCRoutes = [
            [
                "MR_id" => "KRTC-C",
                "MR_name_TC" => "輕軌",
                "MR_name_EN" => "Light Rail",
                "MR_system_id" => "KRTC",
            ]
        ];

        $metroRouteModel = new MetroRouteModel();

        foreach ($krtcCRoutes as $krtcCRoute)
        {
            $metroRouteModel->save($krtcCRoute);
        }
    }
}
