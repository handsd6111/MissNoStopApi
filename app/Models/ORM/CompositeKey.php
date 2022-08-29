<?php

namespace App\Models\ORM;
use CodeIgniter\Model;

Class CompositeKey extends Model
{
    protected $compositePrimaryKeys = []; // 複合主鍵列表

    /**
     * 利用 where 查詢此筆資料(複合主鍵)是否存在於 SQL 中了。
     * @param array $data 欲儲存的資料
     */
    public function isKeyInTable($data)
    {
        // 將複合主鍵列表中的 Key 帶入 data 中取值，並且比對 SQL Table 中的資料。
        foreach ($this->compositePrimaryKeys as $key) {
            $this->builder->where($key, $data[$key]); 
        }

        return $this->builder->countAllResults() > 0 ? true : false; // 以取出來的數量來判斷是否已有此筆資料了。
    }

    /**
     * 複寫原本 Model 中的 save function，將原本只能單個主鍵變成可以使用複合主鍵。
     * @param array $data 欲儲存的資料
     */
    public function save($data): bool
    {
        // 如果沒帶資料直接完成，則傳回 true。
        if (empty($data)) {
            return true;
        }
        
        // 判斷 SQL 內是否已經有此筆資料了，有則更新，無則寫入。
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
