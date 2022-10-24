<?php
    /**
     * 檢查並將時刻為「0」點的時間改為「24」點
     * @param string &$time 時間
     * @return void 不回傳值
     */
    function time_00_to_24($time)
    {
        try
        {
            if ($time[0] == '0' && $time[1] == '0')
            {
                $time[0] = '2';
                $time[1] = '4';
            }
            return $time;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
?>