<?php

namespace App\Database\Seeds;

use App\Models\ORM\MetroSubRouteModel;
use CodeIgniter\Database\Seeder;

class KrtcCSubRouteSeeder extends Seeder
{
    public function run()
    {
        $krtcCSubRoutes = [
            [
                "MSR_id" => "KRTC-C",
                "MSR_name_TC" => "輕軌",
                "MSR_name_EN" => "Light Rail",
                "MSR_route_id" => "KRTC-C",
            ]
        ];

        $metroSubRouteModel = new MetroSubRouteModel();

        foreach ($krtcCSubRoutes as $krtcCSubRoute)
        {
            $metroSubRouteModel->save($krtcCSubRoute);
        }
    }
}
