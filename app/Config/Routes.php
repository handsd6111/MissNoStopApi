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

// api
$routes->group('api', static function ($routes)
{
    // 通用 api
    $routes->group('General', static function ($routes)
    {
        // /api/General/City 取得縣市資料
        $routes->get('City', 'ApiGeneralController::get_bus_cities');
    });

    // 公車相關 api
    $routes->group('Bus', static function ($routes)
    {
        $routes->get('City', 'ApiBusController::get_bus_cities');

        // /api/Bus/Route/{CityId} 取得指定縣市的「公車路線」資料
        $routes->get('Route/(:alpha)', 'ApiBusController::get_bus_routes/$1');

        // /api/Bus/StationOfRoute/{RouteId}/{Direction} 取得指定路線及行駛方向的「公車站」資料
        $routes->get('StationOfRoute/(:segment)/(:num)', 'ApiBusController::get_bus_stations/$1/$2');

        // /api/Bus/NearestStation/{Longitude}/{Latitude} 取得指定經緯度的「最近公車站」資料
        $routes->get('NearestStation/(:segment)/(:segment)', 'ApiBusController::get_bus_nearest_station/$1/$2');

        // /api/Bus/Arrival/{RouteId}/{FromStationId}/{ToStationId}/{Direction} 取得指定路線、起訖站及行駛方向的「公車時刻表」資料
        $routes->get('Arrival/(:segment)/(:segment)/(:segment)/(:segment)', 'ApiBusController::get_bus_arrivals/$1/$2/$3/$4');

        // /api/Bus/ArrivalOfRoute/{RouteId}/{Direction}/{Time} 取得指定路線、行駛方向及目前時間的「公車路線時刻表」資料
        $routes->get('ArrivalOfRoute/(:segment)/(:num)/(:segment)', 'ApiBusController::get_bus_arrivals_by_route/$1/$2/$3');
    });

    // 捷運相關 api
    $routes->group('Metro', static function ($routes)
    {
        // /api/Metro/System 取得「捷運系統」資料
        $routes->get('System', 'ApiMetroController::get_metro_systems');

        // /api/Metro/Route/{SystemId} 取得指定系統的「捷運路線」資料
        $routes->get('Route/(:alpha)', 'ApiMetroController::get_metro_routes/$1');
        
        // /api/Metro/StationOfRoute/{RouteId} 取得指定系統及路線的「捷運站」資料
        $routes->get('StationOfRoute/(:segment)', 'ApiMetroController::get_metro_stations/$1');
        
        // /api/Metro/NearestStation/{Longitude}/{Latitude} 取得指定經緯度的「最近捷運站」資料
        $routes->get('NearestStation/(:segment)/(:segment)', 'ApiMetroController::get_metro_nearest_station/$1/$2/$3');
        
        // /api/Metro/Arrival/{FromStationId}/{ToStationId} 取得指定起訖站的「捷運時刻表」資料
        $routes->get('Arrival/(:segment)/(:segment)', 'ApiMetroController::get_metro_arrivals/$1/$2');

        // /api/Metro/ArrivalOfRoute/{RouteId}/{Direction}/{Time} 取得指定路線、行駛方向及目前時間的「捷運路線時刻表」資料
        $routes->get('ArrivalOfRoute/(:segment)/(:num)/(:segment)', 'ApiMetroController::get_metro_arrivals_by_route/$1/$2/$3');

        // /api/Metro/RoutePlan/{FromStationId}/{ToStationId}/{StartTime} 取得指定起訖站及發車時間的「捷運路線規劃」資料
        $routes->get('RoutePlan/(:segment)/(:segment)/(:segment)', 'ApiRoutePlanController::metro_route_plan/$1/$2/$3');
    });

    // 高鐵相關 api
    $routes->group('THSR', static function ($routes)
    {
        // /api/THSR/City 取得「高鐵營運縣市」資料
        $routes->get('City', 'ApiThsrController::get_thsr_cities');

        // /api/THSR/Station 取得「高鐵車站」資料
        $routes->get('Station', 'ApiThsrController::get_thsr_stations');
        
        // /api/THSR/NearestStation/{Longitude}/{Latitude} 取得指定經緯度的「最近高鐵車站」資料
        $routes->get('NearestStation/(:segment)/(:segment)', 'ApiThsrController::get_thsr_nearest_station/$1/$2');
        
        // /api/THSR/Arrival/{FromStationId}/{ToStationId} 取得指定起訖站的「高鐵時刻表」資料
        $routes->get('Arrival/(:segment)/(:segment)', 'ApiThsrController::get_thsr_arrivals/$1/$2');

        // /api/THSR/ArrivalOfTrain/{TrainId} 取得指定車次的「高鐵車次時刻表」資料
        $routes->get('ArrivalOfTrain/(:segment)', 'ApiThsrController::get_thsr_arrivals_by_train/$1');
    });

    // 臺鐵相關 api
    $routes->group('TRA', static function ($routes)
    {
        // /api/TRA/City 取得「臺鐵營運縣市」資料
        $routes->get('City', 'ApiTraController::get_tra_cities');

        // /api/TRA/StationOfCity/{CityId} 取得指定縣市的「臺鐵車站」資料
        $routes->get('StationOfCity/(:segment)', 'ApiTraController::get_stations_by_city/$1');
        
        // /api/TRA/NearestStation/{RouteId}/{Longitude}/{Latitude} 取得指定經緯度的「最近臺鐵車站」資料
        $routes->get('NearestStation/(:segment)/(:segment)', 'ApiTraController::get_tra_nearest_station/$1/$2');
        
        // /api/TRA/Arrival/{FromStationId}/{ToStationId} 取得指定起訖站的「臺鐵時刻表」資料
        $routes->get('Arrival/(:segment)/(:segment)', 'ApiTraController::get_tra_arrivals/$1/$2');

        // /api/TRA/ArrivalOfTrain/{TrainId} 取得指定車次的「臺鐵車次時刻表」資料
        $routes->get('ArrivalOfTrain/(:segment)', 'ApiTraController::get_tra_arrivals_by_train/$1');
    });
});

// tdx
$routes->group('tdx', static function ($routes) {

    // tdx/data
    $routes->group('data', static function ($routes) {
        $routes->cli('city', 'TdxBaseController::getAndSetCities'); // tdx/data/cities 城市資料

        // tdx/data/thsr
        $routes->group('thsr', static function ($routes) {
            $routes->cli('station', 'TdxThsrController::setThsrStation'); // 高鐵車站

            // $routes->cli('train', 'TdxThsrController::setThsrTrain'); // 高鐵車次

            // $routes->cli('arrival', 'TdxThsrController::setThsrArrival'); // 高鐵時刻表

            $routes->cli('trainAndArrival', 'TdxThsrController::setThsrTrainAndArrival'); // 高鐵時刻表與車次
        });

        // tdx/data/tra
        $routes->group('tra', static function ($routes) {
            $routes->cli('station', 'TdxTraController::setTraStation'); // 臺鐵車站

            $routes->cli('route', 'TdxTraController::setTraRoute'); // 臺鐵路線

            $routes->cli('routeStation', 'TdxTraController::setTraRouteStation'); // 臺鐵路線車站

            // $routes->cli('train', 'TdxTraController::setTraTrain'); // 臺鐵車次

            // $routes->cli('arrival', 'TdxTraController::setTraArrival'); // 臺鐵時刻表

            $routes->cli('trainAndArrival', 'TdxTraController::setTraTrainAndArrival'); // 臺鐵時刻表與車次
        });

        // tdx/data/metro
        $routes->group('metro', static function ($routes) {
            $routes->cli('route', 'TdxMetroController::setMetroRouteAll'); // 全部捷運系統路線
            $routes->cli('route/(:alphanum)', 'TdxMetroController::setMetroRoute/$1'); // 全部捷運系統路線

            $routes->cli('subRoute', 'TdxMetroController::setMetroSubRouteAll'); // 全部捷運系統子路線
            $routes->cli('subRoute/(:alphanum)', 'TdxMetroController::setMetroSubRoute/$1'); // 單筆捷運系統的子路線

            $routes->cli('station', 'TdxMetroController::setMetroStationAll'); // 全部捷運系統的站點
            $routes->cli('station/(:alphanum)', 'TdxMetroController::setMetroStation/$1'); // 單個捷運系統的站點

            $routes->cli('routeStation', 'TdxMetroController::setMetroRouteStationAll'); // 全部捷運系統車站與路線的關聯資料
            $routes->cli('routeStation/(:alphanum)', 'TdxMetroController::setMetroRouteStation/$1'); // 單個捷運系統車站與路線的關聯資料
            
            $routes->cli('subRouteStation', 'TdxMetroController::setMetroSubRouteStationAll'); // 全部捷運系統車站與子路線的關聯資料
            $routes->cli('subRouteStation/(:alphanum)', 'TdxMetroController::setMetroSubRouteStation/$1'); // 單個捷運系統車站與子路線的關聯資料
            
            $routes->cli('transfer', 'TdxMetroController::setMetroTransferAll'); // 全部捷運系統的時刻表
            $routes->cli('transfer/(:alphanum)', 'TdxMetroController::setMetroTransfer/$1'); // 單個捷運系統的時刻表

            $routes->cli('arrival/(:alphanum)', 'TdxMetroController::setMetroArrival/$1'); // 單個捷運系統的時刻表

            $routes->cli('duration/(:alphanum)', 'TdxMetroController::setMetroDuration/$1'); // 單個捷運系統的運行時間，不包含 TYMC (桃捷)
        });

        // tdx/data/bus
        $routes->group('bus', static function ($routes) {
            $routes->cli('routeStation', 'TdxBusController::setBusRouteStation'); // 公車路線車站

            $routes->cli('arrival', 'TdxBusController::setBusArrivals'); // 公車車次時刻表
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
