<?php

namespace App\Models\ORM;

use App\Models\ORM\CompositeKey;

class BusDuplicatedStations extends CompositeKey
{
    protected $DBGroup          = 'default';
    protected $table            = 'bus_duplicated_stations';
    protected $primaryKey       = 'BDS_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "BDS_station_id",
        "BDS_duplicated_id"
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->builder();
        $this->compositePrimaryKeys = [
            'BDS_station_id',
            'BDS_duplicated_id'
        ];
    }
}
