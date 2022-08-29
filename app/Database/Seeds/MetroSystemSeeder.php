<?php

namespace App\Database\Seeds;

use App\Models\ORM\MetroSystemModel;
use CodeIgniter\Database\Seeder;

class MetroSystemSeeder extends Seeder
{
    public function run()
    {
        $metroSystems = [
            [
                'MST_id' => 'TRTC',
                'MST_name_TC' => '臺北捷運',
                'MST_name_EN' => 'Taipei Rapid Transit Corporation'
            ],
            [
                'MST_id' => 'KRTC',
                'MST_name_TC' => '高雄捷運',
                'MST_name_EN' => 'Kaohsiung Rapid Transit Corporation',
            ],
            [
                'MST_id' => 'TYMC',
                'MST_name_TC' => '桃園捷運',
                'MST_name_EN' => 'Taoyuan Metro Corporation'
            ]
        ];

        $metroSystemModel = new MetroSystemModel();
        foreach ($metroSystems as $metroSystem) {
            $metroSystemModel->save($metroSystem);
        }
    }
}
