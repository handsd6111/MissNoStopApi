<?php

namespace App\Models\ORM;

use Config\Database;
use CodeIgniter\Model;
// use App\Models\ORM\CompositeKey;

class MetroDurationModel extends Model
{

    // protected $this->compositePrimaryKeys = ['MD_station_id', 'MD_end_station_id'];
    protected $DBGroup          = 'default';
    protected $table            = 'metro_durations';
    protected $primaryKey       = 'MD_station_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'MD_station_id',
        'MD_end_station_id',
        'MD_duration'
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

    // protected $compositePrimaryKeys = [];
    protected $_builder;


    public function __construct()
    {
        parent::__construct();
        // $db      = \Config\Database::connect();
        // $this->_builder = $db->table('metro_durations');
        // $this->compositePrimaryKeys = ['MD_station_id', 'MD_end_station_id'];
        // var_dump($this->_builder->get());
    }

    /**
     * @param $data array 要輸入的資料
     */
    public function isKeyInTable($data)
    {
        $compositePrimaryKeys = ['MD_station_id', 'MD_end_station_id'];
        $db      = \Config\Database::connect();
        $_builder = $db->table('metro_durations');
        // $_builder->where('MD_station_id', 'TRTC-BL22')->where('MD_end_station_id', 'TRTC-BL23');

        foreach ($compositePrimaryKeys as $key) {
            $_builder->where($key, $data[$key]);
        }

        return $_builder->countAllResults() > 0 ? true : false;
    }

    public function save($data): bool
    {
        $db      = \Config\Database::connect();
        $_builder = $db->table('metro_durations');
        $compositePrimaryKeys = ['MD_station_id', 'MD_end_station_id'];

        if (empty($data)) {
            return true;
        }
        if ($this->isKeyInTable($data) === true) {
            foreach ($compositePrimaryKeys as $key) {
                $_builder->where($key, $data[$key]);
            }
            $response = $_builder->update($data);
        } else {
            $response = $_builder->insert($data);

            if ($response !== false) {
                $response = true;
            }
        }

        return $response;
    }

    // use CompositeKey;
}
