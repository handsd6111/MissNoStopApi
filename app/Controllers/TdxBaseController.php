<?php

namespace App\Controllers;

use App\Models\ORM\CityModel;
use Exception;
use App\Controllers\BaseController;
use App\Models\TdxAuth;
use \Config\Services as CS;
use CodeIgniter\CLI\CLI;

class TdxBaseController extends BaseController
{
    /**
     * 從 AuthObject 中取出 Access Token。
     * @return string
     */
    protected function getAccessToken()
    {
        return TdxAuth::getAuthObject()->access_token;
    }

    /**
     * 從 TDX 取得城市資料，並且利用 ORM Model 寫入 SQL 內。
     * 
     * @return boolean true | false
     */
    public function getAndSetCities()
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://tdx.transportdata.tw/api/basic/v2/Basic/City?%24format=JSON";
            $result = $this->curlGet($url, $accessToken);
            var_dump($result[0]->CityID);
            foreach ($result as $value) {

                $saveData = [
                    'C_id' => $value->CityCode,
                    'C_name_TC' => $value->CityName,
                    'C_name_EN' => $value->City
                ];

                $cityModel = new CityModel();
                $cityModel->save($saveData); //orm save data
            }
        } catch (Exception $ex) {
            log_message("critical", $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 包裝原本 Codeigniter 中有的 curl Request 為TDX使用時預先帶入 Token。
     */
    protected function curlGet($url, $accessToken, $headers = null)
    {
        if ($headers === null) {
            $headers = [
                'accept' => 'application/json',
                'authorization' => 'Bearer ' . $accessToken
            ];
        }
        $client = CS::curlrequest(); // 建立CURL instance
        $response = $client->request(
            'GET', // method
            $url, // url
            [
                'headers' => $headers
            ] // option
        );

        // 先取得回傳的JSON字串，不過是亂的編碼，做第一次deocde時會將亂的編碼變成正確的編碼，第二次decode才會將JSON字串轉成物件
        $result = json_decode(json_decode($response->getJSON()));
        return $result;
    }

    /**
     * 回傳指定前綴與代碼的 UID
     * @param &$prefix 前綴
     * @param &$id 代碼
     * @return string UID
     */
    protected function getUID(&$prefix, &$id)
    {
        try
        {
            return $prefix . "-" . $id;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 在終端顯示訊息
     * @param string $message 訊息
     * @param bool $lineBreak 是否換行
     * @param void 不回傳值
     */
    protected function terminalLog($message = "", $lineBreak = false, $preLineBreak = false)
    {
        try
        {
            $cli = new CLI();
            if ($preLineBreak)
            {
                $cli::newLine();
            }
            $cli::print($message);
            if ($lineBreak)
            {
                $cli::newLine();
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

    /**
     * 取得當前時間
     * @return string 當前時間
     */
    protected function getTime()
    {
        try
        {
            return microtime(true);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 取得指定起始時間到現在的總花費時間
     * @param $startTime 起始時間
     * @return string 總花費時間
     */
    protected function getTimeTaken($startTime)
    {
        try
        {
            return floor(($this->getTime() - $startTime) * 1000) / 1000;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
