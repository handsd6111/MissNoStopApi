<?php
    function sec_to_time($second)
    {
        try
        {
            $second = intval($second);
            $hour = "00";
            if ($second >= 3600)
            {
                $hour = get_two_digit_number(intdiv($second,3600));
            }
            $second %= 3600;
            $minute = "00";
            if ($second >= 60)
            {
                $minute = get_two_digit_number(intdiv($second, 60));
            }
            $second = get_two_digit_number($second % 60);
            return "$hour:$minute:$second";
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function get_two_digit_number($number)
    {
        try
        {
            if ($number < 10)
            {
                return "0$number";
            }
            return strval($number);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
?>