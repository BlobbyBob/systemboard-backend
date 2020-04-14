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

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use systemboard\services\DefaultService;
use Systemboard\Services\WallService;

$getWallHandler = function (Request $request, Response $response, $args) {
    /** @var DefaultService $defaultService */
    $defaultService = $this->get('defaultService');

    if ($this->has('wallService')) {
        /** @var WallService $wallService */
        $wallService = $this->get('wallService');
        return $wallService->get($request, $response, $args);
    }

    return $defaultService->notImplemented($request, $response, $args);
};