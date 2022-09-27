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

// 取得所有縣市資料 /api/city
$routes->get('/api/city', 'ApiController::get_cities');

// 取得所有捷運系統資料
$routes->get('/api/metro/system', 'ApiController::get_metro_systems');
// 取得指定捷運系統上的所有路線資料
$routes->get('/api/metro/system/(:alpha)', 'ApiController::get_metro_routes/$1');
// 取得指定捷運系統及路線上的所有捷運站資料
$routes->get('/api/metro/system/(:alpha)/route/(:segment)', 'ApiController::get_metro_stations/$1/$2');
// 取得指定捷運系統、路線及經緯度的最近捷運站資料
$routes->get('/api/metro/system/(:alpha)/route/(:segment)/long/(:segment)/lat/(:segment)', 'ApiController::get_metro_nearest_station/$1/$2/$3/$4');
// 取得指定捷運起訖站的時刻表資料
$routes->get('/api/metro/arrival/from/(:segment)/to/(:segment)', 'ApiController::get_metro_arrivals/$1/$2');
// 取得指定捷運起訖站的運行時間資料
$routes->get('/api/metro/duration/from/(:segment)/to/(:segment)', 'ApiController::get_metro_durations/$1/$2');

// 取得所有高鐵站資料
$routes->get('/api/THSR/station', 'ApiController::get_thsr_stations');
// 取得指定經緯度的最近高鐵站資料
$routes->get('/api/THSR/station/long/(:segment)/lat/(:segment)', 'ApiController::get_thsr_nearest_station/$1/$2');
// 取得指定高鐵起訖站的時刻表資料
$routes->get('/api/THSR/arrival/from/(:segment)/to/(:segment)', 'ApiController::get_thsr_arrivals/$1/$2');

// 取得所有臺鐵路線資料
$routes->get('/api/TRA/route', 'ApiController::get_tra_routes');
// 取得指定臺鐵路線的所有臺鐵站資料
$routes->get('/api/TRA/route/(:segment)', 'ApiController::get_tra_stations/$1');
// 取得指定臺鐵路線及經緯度的最近臺鐵站資料
$routes->get('/api/TRA/route/(:segment)/long/(:segment)/lat/(:segment)', 'ApiController::get_tra_nearest_station/$1/$2/$3');
// 取得指定臺鐵起訖站的時刻表資料
$routes->get('/api/TRA/arrival/from/(:segment)/to/(:segment)', 'ApiController::get_tra_arrivals/$1/$2');

// 取得指定公車縣市的所有路線資料
$routes->get('/api/bus/city/(:alpha)/route', 'ApiController::get_bus_routes/$1');
// 取得指定公車路線的所有車站資料
$routes->get('/api/bus/route/(:segment)/station', 'ApiController::get_bus_stations/$1');
// 取得指定公車路線及經緯度的最近車站資料
$routes->get('/api/bus/route/(:segment)/station/long/(:segment)/lat/(:segment)', 'ApiController::get_bus_nearest_station/$1/$2/$3');
// 取得指定公車起訖站的時刻表資料
$routes->get('/api/bus/arrival/from/(:segment)/to/(:segment)', 'ApiController::get_bus_arrivals/$1/$2');

// tdx
$routes->group('tdx', static function ($routes) {

    // tdx/data
    $routes->group('data', static function ($routes) {
        $routes->cli('city', 'TdxBaseController::getAndSetCities'); // tdx/data/cities 城市資料

        // tdx/data/thsr
        $routes->group('thsr', static function ($routes) {
            $routes->cli('station', 'TdxThsrController::setThsrStation'); // 高鐵車站

            $routes->cli('train', 'TdxThsrController::setThsrTrain'); // 高鐵車次

            $routes->cli('arrival', 'TdxThsrController::setThsrArrival'); // 高鐵時刻表

            $routes->cli('trainAndArrival', 'TdxThsrController::setThsrTrainAndArrival'); // 高鐵時刻表與車次
        });

        // tdx/data/tra
        $routes->group('tra', static function ($routes) {
            $routes->cli('station', 'TdxTraController::setTraStation'); // 臺鐵車站

            $routes->cli('route', 'TdxTraController::setTraRoute'); // 臺鐵路線

            $routes->cli('routeStation', 'TdxTraController::setTraRouteStation'); // 臺鐵路線車站

            $routes->cli('train', 'TdxTraController::setTraTrain'); // 臺鐵車次

            $routes->cli('arrival', 'TdxTraController::setTraArrival'); // 臺鐵時刻表

            $routes->cli('trainAndArrival', 'TdxTraController::setTraTrainAndArrival'); // 臺鐵時刻表與車次
        });

        // tdx/data/metro
        $routes->group('metro', static function ($routes) {

            $routes->cli('route/all', 'TdxMetroController::setMetroRouteAll'); // 全部捷運系統路線
            $routes->cli('route/(:alphanum)', 'TdxMetroController::setMetroRoute/$1'); // 單筆捷運系統的路線

            $routes->cli('station/all', 'TdxMetroController::setMetroStationAll'); // 全部捷運系統的站點
            $routes->cli('station/(:alphanum)', 'TdxMetroController::setMetroStation/$1'); // 單個捷運系統的站點 

            $routes->cli('duration/(:alphanum)', 'TdxMetroController::setMetroDuration/$1'); // 單個捷運系統的運行時間，不包含 TYMC (桃捷)

            $routes->cli('routeStation/all', 'TdxMetroController::setMetroRouteStationAll'); // 全部捷運系統車站與路線的關聯資料
            $routes->cli('routeStation/(:alphanum)', 'TdxMetroController::setMetroRouteStation/$1'); // 單個捷運系統車站與路線的關聯資料

            $routes->cli('arrival/(:alphanum)', 'TdxMetroController::setMetroArrival/$1'); // 單個捷運系統的時刻表
        });

        // tdx/data/bus
        $routes->group('bus', static function ($routes) {
            //$routes->cli('', ''); // 公車
            $routes->cli('routeStation', 'TdxBusController::setBusRouteStation'); // 公車路線車站

            $routes->cli('arrival', 'TdxBusController::setBusArrivalAndCar'); // 公車時刻表

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
