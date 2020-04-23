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

use Slim\Factory\AppFactory;

require 'handler.php';
require 'vendor/autoload.php';
require 'src/autoload.php';

$services = require 'services.php';

AppFactory::setContainer($services);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/stats', $getStatsHandler);
$app->get('/wall', $getWallHandler);
$app->get('/wall/{id}', $getWallHandler);
$app->get('/holds/{wall}', $getHoldsHandler);
$app->get('/boulder/{id}', $getBoulderByIdHandler);
$app->get('/login/{authtype}/{email}', $getLoginHandler);
$app->get('/user/{id}', $getUserPrivateHandler);
$app->get('/profile/{id}', $getUserPublicHandler);

$app->post('/boulder', $postBoulderHandler);

$app->put('/user/{id}', $putUserHandler);
$app->put('/boulder/{id}/climbed', $putBoulderClimbedHandler);

$app->run();
