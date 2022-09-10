<?php

namespace App\Models;

use App\Controllers\TDXAuthController;
use CodeIgniter\Model;
use Config\Services as CS;
use DateTime;

class TDXAuth
{
    /**
     * 取得快取中的 TDXAuthObject。
     * 
     * @return object(stdClass) 
     * {
     *      access_token => string
     *      expires_in => int
     *      refresh_expires_in => int
     *      token_type => string
     *      not-before-policy => int
     *      scope =>  string
     * }
     */
    public static function getAuthObject()
    {
        $TDXAuthObject = CS::cache()->get('TDXAuthObject');
        if ($TDXAuthObject === NULL) {
            $TDXAuthObject = TDXAuthController::getAndSetAuthObject();
        }
        return $TDXAuthObject;
    }

    /**
     * 將 TDXAuthObject 放入 cache 中。
     * 
     * @param object $authObject TDXAuthObject
     */
    public static function setAuthObject($authObject)
    {
        $expires_in = $authObject->expires_in;
        $authObject->expires_in = time() + $expires_in;
        CS::cache()->save('TDXAuthObject', $authObject, $expires_in);
    }
}
