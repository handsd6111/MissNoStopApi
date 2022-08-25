<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TDXAuth;
use \Config\Services as CS;

class TDXBaseController extends BaseController
{
    protected function getAccessToken()
    {
        return TDXAuth::getAuthObject()->access_token;
    }

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

    protected function curlPost()
    {
    }
}
