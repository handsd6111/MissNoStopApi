<?php
    /**
     * 取得當前分鐘時間
     */
    function get_time_minute()
    {
        try
        {
            $nowTime   = explode(":", date("H:i"));
            return intval($nowTime[0]) * 60 + intval($nowTime[1]);
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            throw $e;
        }
    }
?>