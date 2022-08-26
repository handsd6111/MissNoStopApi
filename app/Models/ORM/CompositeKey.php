<?php

namespace App\Models\ORM;
use CodeIgniter\Model;

Class CompositeKey extends Model
{
    protected $compositePrimaryKeys = [];

    /**
     * @param $data array 要輸入的資料
     */
    public function isKeyInTable($data)
    {

        foreach ($this->compositePrimaryKeys as $key) {
            $this->builder->where($key, $data[$key]);
        }

        return $this->builder->countAllResults() > 0 ? true : false;
    }

    public function save($data): bool
    {

        if (empty($data)) {
            return true;
        }
        if ($this->isKeyInTable($data) === true) {
            foreach ($this->compositePrimaryKeys as $key) {
                $this->builder->where($key, $data[$key]);
            }
            $response = $this->builder->update($data);
        } else {
            $response = $this->builder->insert($data);

            if ($response !== false) {
                $response = true;
            }
        }

        return $response;
    }
}
