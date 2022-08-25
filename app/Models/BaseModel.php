<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class BaseModel extends Model
{
    /**
     * 取得所有縣市資料的查詢類別（未執行 Query）
     * @return mixed 縣市資料查詢類別
     */
    function get_cities()
    {
        try
        {
            return $this->db->table("cities")
                            ->select("*");
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
}