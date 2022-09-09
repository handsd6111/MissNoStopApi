<?php

namespace App\Models;

use App\Controllers\TdxAuthController;
use CodeIgniter\Model;
use Config\Services as CS;
use DateTime;

class TdxAuth
{
    /**
     * 取得快取中的 TdxAuthObject。
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
        $TdxAuthObject = CS::cache()->get('TdxAuthObject');
        if ($TdxAuthObject === NULL) {
            $TdxAuthObject = TdxAuthController::getAndSetAuthObject();
        }
        return $TdxAuthObject;
    }

    /**
     * 將 TdxAuthObject 放入 cache 中。
     * 
     * @param object $authObject TdxAuthObject
     */
    public static function setAuthObject($authObject)
    {
        $expires_in = $authObject->expires_in;
        $authObject->expires_in = time() + $expires_in;
        CS::cache()->save('TdxAuthObject', $authObject, $expires_in);
    }
}
