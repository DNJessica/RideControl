<?php

use CodeIgniter\Router\RouteCollection;


/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('api/coasters/(:segment)/wagons', 'WagonController::create/$1');
$routes->delete('api/coasters/(:segment)/wagons/(:segment)', 'WagonController::delete/$1/$2');
$routes->post('api/coasters', 'CoasterController::create');
$routes->put('api/coasters/(:segment)', 'CoasterController::change/$1');
$routes->get('api/coasters/(:segment)/check-personel', 'CoasterController::checkPersonel/$1');
$routes->get('api/coasters/(:segment)/check-clients', 'CoasterController::checkClients/$1');
