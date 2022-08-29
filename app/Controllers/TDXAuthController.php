<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TDXAuth;
use \Config\Services as CS;
use Exception;

class TDXAuthController extends TDXBaseController
{

    /**
     * 從 TDX 的 Auth2 Server 取得 AuthObject 並且呼叫 TDXAuth 中的 setAuthObject 將之寫入 cache 中。
     * @return false | object(stdClass)
     * {
     *      access_token => string
     *      expires_in => int
     *      refresh_expires_in => int
     *      token_type => string
     *      not-before-policy => int
     *      scope =>  string
     * }
     */
    public static function getAndSetAuthObject()
    {
        try {
            $url = "https://tdx.transportdata.tw/auth/realms/TDXConnect/protocol/openid-connect/token";

            $postData = array(
                "grant_type" => getenv("tdx.grantType"),
                "client_id" => getenv("tdx.clientId"),
                "client_secret" => getenv("tdx.clientSecret")
            );

            $client = CS::curlrequest();
            $response = $client->request(
                'POST',
                $url,
                [
                    'headers' => [
                        'content-type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => $postData
                ]
            );
            $result = json_decode(json_decode($response->getJSON()));
            TDXAuth::setAuthObject($result);
            return $result;
        } catch (Exception $ex) {
            throw $ex;
            return false;
        }
    }
}
