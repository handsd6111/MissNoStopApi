<?php

namespace App\Models\ORM;

use App\Models\ORM\CompositeKey;

class BusArrivalModel extends CompositeKey
{
    protected $DBGroup          = 'default';
    protected $table            = 'bus_arrivals';
    protected $primaryKey       = 'BA_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "BA_station_id",
        "BA_route_id",
        "BA_trip_id",
        "BA_direction",
        "BA_arrival_time",
        "BA_departure_time",
        "BA_arrives_today",
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
            'BA_station_id',
            'BA_route_id',
            "BA_trip_id",
            "BA_direction"
        ];
    }
}
