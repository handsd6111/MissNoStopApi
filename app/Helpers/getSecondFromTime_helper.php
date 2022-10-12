<?php
    /**
     * 取得指定時間從午夜起的秒數
     * @param string $time 時間（hh:mm:ss）
     * @return int 秒數
     */
    function get_second_from_time($time)
    {
        try
        {
            $timeData = explode(":", $time);

            $hour   = intval($timeData[0]);
            $minute = intval($timeData[1]);
            $second = intval($timeData[2]);

            return $hour * 3600 + $minute * 60 + $second;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
?>