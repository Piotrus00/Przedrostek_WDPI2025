<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/RouletteController.php';
require_once 'src/controllers/StatisticsController.php';
require_once 'src/controllers/UpgradesController.php';
require_once 'src/controllers/AdminPanelController.php';
require_once 'src/controllers/BalanceController.php';
require_once 'src/middleware/checkRequestAllowed.php';
require_once 'src/middleware/checkAuthRequirements.php';
class Routing
{
    public static $routes = [
        'login' => [
            'controller' => 'SecurityController',
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'SecurityController',
            'action' => 'register'
        ],
        'dashboard' => [
            'controller' => 'DashboardController',
            'action' => 'index'
        ],
        'search-cards' => [
            'controller' => 'DashboardController',
            'action' => 'search'
        ],
        'roulette' => [
            'controller' => 'RouletteController',
            'action' => 'index'
        ],
        'api/roulette' => [
            'controller' => 'RouletteController',
            'action' => 'gameApi'
        ],
        'api/balance' => [
            'controller' => 'BalanceController',
            'action' => 'balanceApi'
        ],
        'api/upgrades' => [
            'controller' => 'UpgradesController',
            'action' => 'upgradesApi'
        ],
        'statistics' => [
            'controller' => 'StatisticsController',
            'action' => 'index'
        ],
        'upgrades' => [
            'controller' => 'UpgradesController',
            'action' => 'index'
        ],
        'admin-panel' => [
            'controller' => 'AdminPanelController',
            'action' => 'index'
        ],
        'logout' => [
            'controller' => 'SecurityController',
            'action' => 'logout'
        ],
    ];
    private static $instances = [];
    public static function run(string $path)
    {
        if (array_key_exists($path, self::$routes)) {
            $controllerName = self::$routes[$path]['controller'];
            $action = self::$routes[$path]['action'];

            $object = self::getControllerInstance($controllerName);

            checkRequestAllowed($object, $action);
            checkAuthRequirements($object, $action);

            $object->$action();
            return;
        }
        if (preg_match('#^card-details/([0-9]+)$#', $path, $matches)) {

            $id = $matches[1];

            $object = self::getControllerInstance('DashboardController');

            $object->details($id);

            return;
        }
        include 'public/views/404.html';
    }
    private static function getControllerInstance($controllerName) {
        if (!isset(self::$instances[$controllerName])) {
            self::$instances[$controllerName] = new $controllerName();
        }
        return self::$instances[$controllerName];
    }
}