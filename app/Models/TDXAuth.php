<?php

namespace App\Models;

use App\Controllers\TDXAuthController;
use CodeIgniter\Model;
use Config\Services as CS;
use DateTime;

class TDXAuth
{
    public static function getAuthObject()
    {
        $TDXAuthObject = CS::cache()->get('TDXAuthObject');

        if ($TDXAuthObject === NULL) {
            TDXAuthController::getAndSetAuthObject();
            // return false;
        }

        return $TDXAuthObject;
    }

    public static function setAuthObject($authObject)
    {
        $expires_in = $authObject->expires_in;
        $authObject->expires_in = time() + $expires_in;
        CS::cache()->save('TDXAuthObject', $authObject, $expires_in);
    }
}
