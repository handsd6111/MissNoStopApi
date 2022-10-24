<?php
    /**
     * 為時間加上秒數
     * @param string $time 時間
     * @param int $second 秒數
     * @return string 時間
     */
    function add_time($time, $second)
    {
        try
        {
            helper(["getTimeToSecond", "getSecondToTime"]);
            $sec1 = time_to_sec($time);
            $sec2 = $second;
            $sec3 = intval($sec1) + intval($sec2);
            return sec_to_time($sec3);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
?>