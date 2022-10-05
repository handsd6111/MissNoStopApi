<?php

use CodeIgniter\CLI\CLI;

    /**
     * 在終端顯示訊息
     * @param string $message
     * @param bool $lineBreak
     * @param void 不回傳值
     */
    function terminal_log($message, $lineBreak = false)
    {
        try
        {
            $cli = new CLI();
            if ($lineBreak)
            {
                $cli::write($message);
            }
            else
            {
                $cli::print($message);
            }
            if (ob_get_level() > 0)
            {
                ob_end_flush();  
            }    
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
?>