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

namespace Systemboard\Services;


use Slim\Psr7\Request;
use Slim\Psr7\Response;

class DefaultService
{

    public static function badRequest(Request $request, Response $response)
    {
        $response->getBody()->write('Bad Request');
        return $response
            ->withStatus(400, 'Bad Request')
            ->withHeader('Content-Type', 'text/plain; charset=utf8');
    }

    public static function notFound(Request $request, Response $response)
    {
        $response->getBody()->write('Not Found');
        return $response
            ->withStatus(404, 'Not Found')
            ->withHeader('Content-Type', 'text/plain; charset=utf8');
    }

    public static function notImplemented(Request $request, Response $response) {
        $response->getBody()->write('501 Not Implemented');
        return $response
            ->withStatus(501, 'Not Implemented')
            ->withHeader("Content-Type", "text/plain");
    }
}