<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
//$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
// 取得所有捷運系統
$routes->get('/api/metro/system', 'ApiController::get_metro_systems');
// 取得指定捷運系統上的所有路線
$routes->get('/api/metro/system/(:alpha)', 'ApiController::get_metro_routes/$1');
// 取得指定捷運系統及路線上的所有車站
$routes->get('/api/metro/system/(:alpha)/route/(:segment)', 'ApiController::get_metro_stations/$1/$2');
// 取得指定車站及終點車站方向的時刻表
$routes->get('/api/metro/arrival/station/(:segment)/end-station/(:segment)', 'ApiController::get_metro_arrivals/$1/$2');

$routes->group('tdx', static function ($routes) {
    // $routes->cli('auth', 'TDXAuthController::getAndSetAuthObject');
    $routes->group('data', static function ($routes) {
        $routes->cli('cities', 'TDXDataController::getAndSetCities');

        $routes->group('metro', static function ($routes) {
            $routes->cli('station/(:alphanum)', 'TDXDataController::getAndSetMetroStation/$1');
            $routes->cli('route/(:alphanum)', 'TDXDataController::getAndSetMetroRoute/$1');
            $routes->cli('duration/TYMC', 'TDXDataController::getAndSetMetroDurationForTYMC');
            $routes->cli('duration/(:alphanum)', 'TDXDataController::getAndSetMetroDuration/$1');
        });
    });
});


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
