<?php

namespace App\Models\ORM;

trait CompositeKey
{
    protected $compositePrimaryKeys = [];

    /**
     * @param $data array 要輸入的資料
     */
    private function isKeyInTable($data)
    {
        foreach ($this->compositePrimaryKeys as $key) {
            $this->builder->where($key, $data[$key]);
        }
        return $this->bulider->get() > 0 ? true : false;
    }

    public function save($data): bool
    {
        if (empty($data)) {
            return true;
        }

        if ($this->isKeyInTable($data)) {
            $response = $this->bulider->update($data);
        } else {
            $response = $this->bulider->insert($data);

            if ($response !== false) {
                $response = true;
            }
        }

        return $response;
    }
}
