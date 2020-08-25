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


use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use PDO;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Systemboard\Entity\User;
use Systemboard\PublicEntity\BoulderStub as PublicBoulderStub;
use Systemboard\PublicEntity\UserInfo as PublicUserInfo;
use Systemboard\PublicEntity\UserProfile as PublicUserProfile;
use Systemboard\PublicEntity\UserStats as PublicUserStats;
use Systemboard\PublicEntity\Ranking as PublicRanking;

class UserService extends AbstractService
{
    public function getPrivate(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        $id = (int) ($args['id'] ?? 0);

        $user = User::load($this->pdo, $id);
        if (is_null($user)) {
            return DefaultService::notFound($request, $response);
        }
        if ($user->id != $request->getAttribute('user')->id) {
            return DefaultService::forbidden($request, $response);
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
        if ($request->getAttribute('role') != 'user' && $request->getAttribute('role') != 'guest') {
            return DefaultService::forbidden($request, $response);
        }

        $id = (int) ($args['id'] ?? 0);
        $wallid = (int) ($request->getQueryParams()['wall'] ?? 0);

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

    public function getRanking(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user' && $request->getAttribute('role') != 'guest') {
            return DefaultService::forbidden($request, $response);
        }

        $responseObject = [];

        $stmt = $this->pdo->prepare('SELECT * FROM ranking ORDER BY score DESC');
        if (!$stmt->execute()) {
            return DefaultService::internalServerError($request, $response);
        }
        while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            $ranking = new PublicRanking();
            [$ranking->id, $ranking->name, $ranking->badge, $ranking->score] = $row;
            $responseObject[] = $ranking;
        }

        $response->getBody()->write(json_encode($responseObject));

        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function put(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/userPut.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $userid = (int) ($args['id'] ?? 0);
        $user = User::load($this->pdo, $userid);

        if ($user->id != $request->getAttribute('user')->id) {
            return DefaultService::forbidden($request, $response);
        }

        if (isset($data->id)) {
            if ($user->id != $data->id) {
                return DefaultService::badRequest($request, $response);
            }
        }

        if (isset($data->name)) {
            $user->name = (string) $data->name;
        }

        if (isset($data->password)) {
            $hash = password_hash($data->password, PASSWORD_ARGON2I, ARGON_SETTINGS);
            if (!$hash) {
                return DefaultService::internalServerError($request, $response);
            }
            $user->password = $hash;
        }

        if (isset($data->newsletter)) {
            $user->newsletter = (int) $data->newsletter;
        }

        if (!$user->save($this->pdo)) {
            return DefaultService::badRequest($request, $response);
        }

        return $response
            ->withStatus(204, 'No Content')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    private function statsForUser(User $user, ?int $wallid = null): PublicUserStats
    {
        $stats = new PublicUserStats();
        $stats->userid = $user->id;
        $stats->ascents = [];

        if (is_null($wallid)) {
            // Get cumulated stats
            $stmt = $this->pdo->prepare('SELECT bm.id, bm.name, CAST(CEIL(MAX(bowv.wall) / ?) AS INTEGER) FROM climbed c LEFT JOIN boulder_meta bm on c.boulder = bm.id
                        JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ? GROUP BY bm.id');
            if ($stmt->execute([SEGMENTS_PER_WALL, $user->id])) {
                while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                    $stub = new PublicBoulderStub();
                    [$stub->id, $stub->name, $stub->wall] = $row;
                    $stats->ascents[] = $stub;
                }
            }
            $stmt = $this->pdo->prepare('SELECT IFNULL(FLOOR(SUM(grade)), 0) FROM grade_view WHERE boulder IN (SELECT c.boulder FROM climbed c WHERE c.user = ?)');
            if ($stmt->execute([$user->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $stats->points = (int) $row[0];
            }
        } else {
            // Get per wall stats
            $stmt = $this->pdo->prepare('SELECT bm.id, bm.name, CAST(CEIL(MAX(bowv.wall) / ?) AS INTEGER) FROM climbed c LEFT JOIN boulder_meta bm on c.boulder = bm.id
                        LEFT JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ? AND bowv.wall IN (SELECT ws.id FROM wall_segment ws WHERE ws.wall = ?) GROUP BY bm.id');
            if ($stmt->execute([SEGMENTS_PER_WALL, $user->id, $wallid])) {
                while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                    $stub = new PublicBoulderStub();
                    [$stub->id, $stub->name, $stub->wall] = $row;
                    $stats->ascents[] = $stub;
                }
            }
            $stmt = $this->pdo->prepare('SELECT IFNULL(FLOOR(SUM(grade)), 0) FROM grade_view WHERE boulder IN (SELECT c.boulder FROM climbed c 
                        LEFT JOIN boulder_on_wall_view bowv on c.boulder = bowv.boulder WHERE c.user = ? AND bowv.wall IN (SELECT ws.id FROM wall_segment ws WHERE ws.wall = ?))');
            if ($stmt->execute([$user->id, $wallid]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $stats->points = (int) $row[0];
            }
        }

        return $stats;
    }
}