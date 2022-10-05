<?php
    /**
     * 回傳今天星期幾
     * @param bool $returnAsString 是否以文字回傳
     */
    function get_week_day($returnAsString = false)
    {
        try
        {
            $day = date('w', time());
            if ($returnAsString)
            {
                $daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                return $daysOfWeek[$day];
            }
            return $day;
        }
        catch (Exception $e)
        {
            log_message("critical", $e);
        }
    }
?>