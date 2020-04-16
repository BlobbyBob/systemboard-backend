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


use PDO;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Systemboard\Entity\Wall;
use Systemboard\Entity\WallSegment;
use Systemboard\PublicEntity\Hold as PublicHold;
use Systemboard\PublicEntity\Holds as PublicHolds;

class HoldService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(Request $request, Response $response, $args)
    {
        $wallid = (int) ($args['wall'] ?? 0);

        if ($wallid <= 0) {
            return DefaultService::badRequest($request, $response);
        }

        $wall = Wall::load($this->pdo, $wallid);
        if (is_null($wall)) {
            return DefaultService::notFound($request, $response);
        }

        $segments = WallSegment::loadByWall($this->pdo, $wall);

        $responseObject = [];
        foreach ($segments as $segment) {
            $holds = new PublicHolds();
            $holds->filename = $segment->filename;
            $holds->holds = [];
            foreach ($segment->fetchHolds($this->pdo) as $hold) {
                $publicHold = new PublicHold();
                $publicHold->id = $hold->id;
                $publicHold->tag = $hold->tag;
                $publicHold->attr = $hold->attr;
                $holds->holds[] = $publicHold;
            }
            $responseObject[] = $holds;
        }

        $response->getBody()->write(json_encode($responseObject));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }
}