<?php

namespace App\Models\ORM;

use App\Models\ORM\CompositeKey;

class BusDurationModel extends CompositeKey
{
    protected $DBGroup          = 'default';
    protected $table            = 'bus_durations';
    protected $primaryKey       = 'BD_from_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "BD_from_station_id",
        "BD_to_station_id",
        "BD_week",
        "BD_hour",
        "BD_duration"
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
            'BD_from_station_id',
            'BD_to_station_id',
            'BD_week',
            'BD_hour'
        ];
    }
}
