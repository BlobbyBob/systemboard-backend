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
use Systemboard\Services\DefaultService;
use Systemboard\Services\HelloService;

require 'config.php';

$services = new Container();

// Base services
$services->set('database', fn() => new PDO(DB_DSN, DB_USER, DB_PASS));
$services->set('defaultService', fn() => new DefaultService());

$services->set('helloService', fn() => new HelloService($services->get('database')));

return $services;
