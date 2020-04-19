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
use Systemboard\Entity\Boulder;
use Systemboard\Entity\Hold;
use Systemboard\Entity\User;
use Systemboard\PublicEntity\Boulder as PublicBoulder;
use Systemboard\PublicEntity\Creator as PublicCreator;
use Systemboard\PublicEntity\Location as PublicLocation;

class BoulderService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById(Request $request, Response $response, $args)
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            return DefaultService::badRequest($request, $response);
        }

        $boulder = Boulder::load($this->pdo, $id);
        if (is_null($boulder)) {
            return DefaultService::notFound($request, $response);
        }

        $responseObject = new PublicBoulder();
        $responseObject->id = $boulder->id;
        $responseObject->name = $boulder->name;
        $responseObject->description = $boulder->description;
        $responseObject->ascents = $boulder->fetchAscents($this->pdo);
        $responseObject->climbed = rand(0, 1) ? true : false; // todo replace, when authentication is implemented
        $responseObject->creator = new PublicCreator();
        if (!is_null($boulder->user)) {
            $boulder->user->resolve($this->pdo);
            $responseObject->creator->id = $boulder->user->id;
            $responseObject->creator->name = $boulder->user->name;
        }
        $responseObject->grade = $boulder->getGrade($this->pdo);
        $responseObject->rating = $boulder->getRating($this->pdo);
        $responseObject->location = new PublicLocation();
        $min = SEGMENTS_PER_WALL - 1;
        $max = 0;
        $main = [];
        for ($i = 0; $i < SEGMENTS_PER_WALL; $i++)
            $main[$i] = 0;
        foreach ($boulder->fetchHolds($this->pdo) as [$hold, $type]) {
            /** @var Hold $hold */
            $hold->resolve($this->pdo);
            if ($type == 2) {
                $main[$hold->wallSegment->id % SEGMENTS_PER_WALL]++;
            }
            $min = min($min, $hold->wallSegment->id % SEGMENTS_PER_WALL);
            $max = max($max, $hold->wallSegment->id % SEGMENTS_PER_WALL);
        }
        $responseObject->location->min = $min;
        $responseObject->location->max = $max;
        $responseObject->location->main = $this->computeMainWall($main);

        $response->getBody()->write(json_encode($responseObject));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function post(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $schema = Schema::fromJsonString(file_get_contents('./schema/boulderAdd.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $holds = [];
        foreach ($data->holds as $hold) {
            $holds[] = [$hold->id, $hold->type];
        }
        $boulder = Boulder::create($this->pdo, $data->name, User::unresolved(1), $data->description, $holds); // todo adjust, when authentication is implemented
        if (is_null($boulder)) {
            return DefaultService::badRequest($request, $response);
        }

        $response->getBody()->write(json_encode($boulder));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    /**
     * Using the amount of special holds on each segment, compute the most important wall.
     * This method uses knowledge of the specifics of the gym
     *
     * @param array $amounts
     *
     * @return int
     */
    private function computeMainWall(array $amounts): int
    {
        if (count($amounts) != 3) {
            $mainIndex = 0;
            $mainAmount = 0;
            foreach ($amounts as $index => $amount) {
                if ($amount > $mainAmount) {
                    $mainIndex = $index;
                    $mainAmount = $amount;
                }
            }
            return $mainIndex;
        }

        if ($amounts[1] >= $amounts[0] && $amounts[1] >= $amounts[2])
            return 1;

        return $amounts[0] >= $amounts[2] ? 0 : 2;
    }
}