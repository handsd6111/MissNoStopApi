<?php

namespace App\Database\Seeds;

use App\Database\Migrations\MetroTransfers;
use App\Models\ORM\MetroTransferModel;
use CodeIgniter\Database\Seeder;

class KlrtTransferSeeder extends Seeder
{
    public function run()
    {
        $klrtTransfers = [
            [
                "MT_from_station_id" => "KRTC-R13",
                "MT_to_station_id" => "KRTC-C24",
                "MT_transfer_time" => "120"
            ],
            [
                "MT_from_station_id" => "KRTC-C24",
                "MT_to_station_id" => "KRTC-R13",
                "MT_transfer_time" => "120"
            ],
            [
                "MT_from_station_id" => "KRTC-O1",
                "MT_to_station_id" => "KRTC-C14",
                "MT_transfer_time" => "120"
            ],
            [
                "MT_from_station_id" => "KRTC-C14",
                "MT_to_station_id" => "KRTC-O1",
                "MT_transfer_time" => "120"
            ],
            [
                "MT_from_station_id" => "KRTC-R6",
                "MT_to_station_id" => "KRTC-C3",
                "MT_transfer_time" => "120"
            ],
            [
                "MT_from_station_id" => "KRTC-C3",
                "MT_to_station_id" => "KRTC-R6",
                "MT_transfer_time" => "120"
            ],
        ];

        $metroTransferModel = new MetroTransferModel();

        foreach ($klrtTransfers as $klrtTransfer)
        {
            $metroTransferModel->save($klrtTransfer);
        }
    }
}
