<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Psr\Log\LoggerInterface;

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
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    /**
     * 參數驗證規則：$metroValidationRules[參數名稱] = 規則
     */
    protected $metroValidationRules = [
        "systemId"     => "alpha|max_length[4]",
        "routeId"      => "alpha_numeric_punct|max_length[12]",
        "stationId"    => "alpha_numeric_punct|max_length[12]",
        "endStationId" => "alpha_numeric_punct|max_length[12]",
        "longitude"    => "alpha_numeric_punct|max_length[12]",
        "latitude"     => "alpha_numeric_punct|max_length[12]"
    ];

    /**
     * 驗證參數
     * @param array $vData 參數陣列
     * @param bool 驗證成功與否
     */
    public function metro_validation($vData)
    {
        try
        {
            // 參數名稱
            $vDataKeys = array_keys($vData);
            // 此次所需的驗證規則
            $vRules = [];

            // 對照 $this->metroValidationRules 的驗證資料及 $vData 的參數名稱，並寫入 $vRules
            for ($i = 0; $i < sizeof($vData); $i++)
            {
                $vRule = $vDataKeys[$i];
                $vRules[$vRule] = $this->metroValidationRules[$vRule];
            }

            if (!$this->validateData($vData, $vRules))
            {
                return false;
            }
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    public function send_response($data = [], $status = 200, $message = "OK", $headers = []) {
        $result = [
            "data" => $data,
            "status" => $status,
            "message" => $message
        ];
        
        $this->response->setJSON($result)->setStatusCode($status);

        return $this->response;
    }
}
