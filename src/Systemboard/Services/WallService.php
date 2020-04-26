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
use Systemboard\PublicEntity\Wall as PublicWall;
use Systemboard\PublicEntity\WallSegment as PublicWallSegment;

class WallService extends AbstractService
{
    public function get(Request $request, Response $response, $args)
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0)
            $id = $this->getCurrentId();

        $wall = Wall::load($this->pdo, $id);
        if (is_null($wall)) {
            return DefaultService::notFound($request, $response);
        }

        $segments = WallSegment::loadByWall($this->pdo, $wall);

        $responseObject = new PublicWall();
        $responseObject->id = $wall->id;
        $responseObject->name = $wall->name;
        $responseObject->wallSegments = [];
        foreach ($segments as $segment) {
            $wallSegment = new PublicWallSegment();
            $wallSegment->image = $segment->filename;
            $responseObject->wallSegments[] = $wallSegment;
        }

        $response->getBody()->write(json_encode($responseObject));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    private function getCurrentId(): int
    {
        $stmt = $this->pdo->prepare('SELECT MAX(id) FROM wall');
        if (!$stmt->execute() || ($row = $stmt->fetch(PDO::FETCH_NUM)) === false)
            return 0;

        return $row[0];
    }
}
