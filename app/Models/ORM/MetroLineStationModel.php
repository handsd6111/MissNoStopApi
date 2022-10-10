<?php

namespace App\Models\ORM;


class MetroLineStationModel extends CompositeKey
{
    protected $DBGroup          = 'default';
    protected $table            = 'metro_line_stations';
    protected $primaryKey       = 'MLS_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'MLS_station_id',
        'MLS_line_id',
        'MLS_sequence'
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
            'MLS_station_id',
            'MLS_line_id'
        ];
    }
}
