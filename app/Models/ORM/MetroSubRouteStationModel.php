<?php

namespace App\Models\ORM;


class MetroSubRouteStationModel extends CompositeKey
{
    protected $DBGroup          = 'default';
    protected $table            = 'metro_sub_route_stations';
    protected $primaryKey       = 'MSRS_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'MSRS_station_id',
        'MSRS_sub_route_id',
        'MSRS_direction',
        'MSRS_sequence'
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
            'MSRS_station_id',
            'MSRS_sub_route_id',
            'MSRS_direction'
        ];
    }
}
