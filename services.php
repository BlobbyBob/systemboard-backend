<?php
/*
 *  systemboard
 *  Copyright (C) 2020 Ben Swierzy
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

use DI\Container;
use Systemboard\Services\BoulderService;
use Systemboard\Services\HoldService;
use Systemboard\Services\AccountService;
use Systemboard\Services\StatsService;
use Systemboard\Services\UserService;
use Systemboard\Services\WallService;

require 'config.php';

$services = new Container();

// Base services
$services->set('database', function () {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
});

$services->set('accountService', fn() => new AccountService($services->get('database')));
$services->set('boulderService', fn() => new BoulderService($services->get('database')));
$services->set('holdService', fn() => new HoldService($services->get('database')));
$services->set('loginService', fn() => new AccountService($services->get('database')));
$services->set('statsService', fn() => new StatsService($services->get('database')));
$services->set('userService', fn() => new UserService($services->get('database')));
$services->set('wallService', fn() => new WallService($services->get('database')));

return $services;
