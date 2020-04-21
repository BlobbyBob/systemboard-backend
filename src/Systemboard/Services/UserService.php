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
use Systemboard\Entity\User;
use Systemboard\PublicEntity\BoulderStub as PublicBoulderStub;
use Systemboard\PublicEntity\UserInfo as PublicUserInfo;
use Systemboard\PublicEntity\UserProfile as PublicUserProfile;
use Systemboard\PublicEntity\UserStats as PublicUserStats;

class UserService extends AbstractService
{
    public function getPrivate(Request $request, Response $response, $args)
    {
        $id = (int) ($args['id'] ?? 0);

        $user = User::load($this->pdo, $id);
        if (is_null($user)) {
            return DefaultService::notFound($request, $response);
        }

        $responseObject = new PublicUserInfo();
        $responseObject->id = $user->id;
        $responseObject->name = $user->name;
        $responseObject->email = $user->email;
        $responseObject->newsletter = $user->newsletter != 0;

        $response->getBody()->write(json_encode($responseObject));

        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function getPublic(Request $request, Response $response, $args)
    {
        $id = (int) ($args['id'] ?? 0);
        $wallid = (int) ($args['wall'] ?? 0);

        $user = User::load($this->pdo, $id);
        if (is_null($user)) {
            return DefaultService::notFound($request, $response);
        }

        $responseObject = new PublicUserProfile();
        $responseObject->id = $user->id;
        $responseObject->name = $user->name;
        $responseObject->current = $this->statsForUser($user, $wallid);
        $responseObject->total = $this->statsForUser($user);

        $response->getBody()->write(json_encode($responseObject));

        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function put(Request $request, Response $response, $args)
    {

    }



    private function statsForUser(User $user, ?int $wallid = null): PublicUserStats
    {
        $stats = new PublicUserStats();
        $stats->userid = $user->id;
        $stats->ascents = [];

        if (is_null($wallid)) {
            // Get cumulated stats
            $stmt = $this->pdo->prepare('SELECT bm.id, bm.name, bowv.wall FROM climbed c LEFT JOIN boulder_meta bm on c.boulder = bm.id
                        LEFT JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ?');
            if ($stmt->execute([$user->id])) {
                while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                    $stub = new PublicBoulderStub();
                    [$stub->id, $stub->name, $stub->wall] = $row;
                    $stats->ascents[] = $stub;
                }
            }
            $stmt = $this->pdo->prepare('SELECT SUM(grade) FROM grade_view WHERE boulder IN (SELECT c.boulder FROM climbed c WHERE c.user = ?)');
            if ($stmt->execute([$user->id]) && $stmt->rowCount() > 0) {
                [$stats->points] = $stmt->fetch(PDO::FETCH_NUM);
            }
        } else {
            // Get per wall stats
            $stmt = $this->pdo->prepare('SELECT bm.id, bm.name, bowv.wall FROM climbed c LEFT JOIN boulder_meta bm on c.boulder = bm.id
                        LEFT JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ? AND bowv.wall = ?');
            if ($stmt->execute([$user->id, $wallid])) {
                while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                    $stub = new PublicBoulderStub();
                    [$stub->id, $stub->name, $stub->wall] = $row;
                    $stats->ascents[] = $stub;
                }
            }
            $stmt = $this->pdo->prepare('SELECT SUM(grade) FROM grade_view WHERE boulder IN (SELECT c.boulder FROM climbed c 
                        LEFT JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ? AND bowv.wall = ?)');
            if ($stmt->execute([$user->id, $wallid]) && $stmt->rowCount() > 0) {
                [$stats->points] = $stmt->fetch(PDO::FETCH_NUM);
            }
        }

        return $stats;
    }
}