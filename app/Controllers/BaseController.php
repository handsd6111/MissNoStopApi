<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Psr\Log\LoggerInterface;
use App\Models\BaseModel;
use App\Models\MetroModel;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Constructor.
     */
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    /**
     * 送出 response
     * @param array $data 回傳資料，預設為空
     * @param int $status 狀態碼，預設為 200（即「成功」）
     * @param string $message 回傳訊息，預設為「OK」
     * @param array $headers 標頭，預設為空
     * @return mixed 回傳資料
     */
    function send_response($data = [], $status = 200, $message = "OK", $headers = [])
    {
        $result = [
            "data" => $data,
            "status" => $status,
            "message" => $message
        ];
        
        $this->response->setJSON($result)->setStatusCode($status);

        return $this->response;
    }
}
