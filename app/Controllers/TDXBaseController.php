<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TDXAuth;
use \Config\Services as CS;

class TDXBaseController extends BaseController
{
    /**
     * 從 AuthObject 中取出 Access Token。
     * @return string
     */
    protected function getAccessToken()
    {
        var_dump(TDXAuth::getAuthObject());
        return TDXAuth::getAuthObject()->access_token;
    }

    /**
     * 包裝原本 Codeigniter 中有的 curl Request 為TDX使用時預先帶入 Token。
     */
    protected function curlGet(
        $url,
        $accessToken,
        $headers = [
            'accept' => 'application/json',
            'authorization' => 'Bearer '
        ]
    ) {

        $headers['authorization'] .= $accessToken;
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
}
