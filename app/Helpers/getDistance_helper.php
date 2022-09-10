<?php
    function get_distance($x1, $y1, $x2, $y2)
    {
        try
        {
            $width  = abs($x1 - $x2);
            $height = abs($y1 - $y2);
            $side   = sqrt(pow($width, 2) + pow($height,2)); 
            return $side;
        }
        catch (Exception $e)
        {
            log_message("critical", $e->getMessage());
            return -1;
        }
    }
?>