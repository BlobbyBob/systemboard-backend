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
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Systemboard\Entity\Hold;
use Systemboard\Entity\User;
use Systemboard\Entity\WallSegment;
use Systemboard\PublicEntity\Hold as PublicHold;

class EditorService extends AbstractService
{
    // todo functions for adding a wall/segment and uploading images
    public function getHold(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        /** @var User $user */
        $user = $request->getAttribute('user');

        if ($user->status < 2) {
            return DefaultService::forbidden($request, $response);
        }

        $holdid = (int)($args['id'] ?? 0);

        if ($holdid <= 0) {
            return DefaultService::badRequest($request, $response);
        }

        $hold = Hold::load($this->pdo, $holdid);
        if (is_null($hold)) {
            return DefaultService::notFound($request, $response);
        }

        $responseObject = new PublicHold();
        $responseObject->id = $hold->id;
        $responseObject->tag = $hold->tag;
        $responseObject->attr = $hold->attr;

        $response->getBody()->write(json_encode($responseObject));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function postHold(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        /** @var User $user */
        $user = $request->getAttribute('user');

        if ($user->status < 2) {
            return DefaultService::forbidden($request, $response);
        }

        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/holdPost.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        if (!in_array($data->tag, ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect', 'text'])) {
            return DefaultService::badRequest($request, $response);
        }

        $wallsegment = WallSegment::loadByFilename($this->pdo, $data->wallSegment);
        if (!$wallsegment) {
            return DefaultService::badRequest($request, $response);
        }

        $hold = Hold::create($this->pdo, $wallsegment->id, $data->tag, $data->attr);
        if (is_null($hold)) {
            return DefaultService::badRequest($request, $response);
        }

        $responseObject = new PublicHold();
        $responseObject->id = $hold->id;
        $responseObject->tag = $hold->tag;
        $responseObject->attr = $hold->attr;

        $response->getBody()->write(json_encode($responseObject));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function putHold(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        /** @var User $user */
        $user = $request->getAttribute('user');

        if ($user->status < 2) {
            return DefaultService::forbidden($request, $response);
        }

        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/holdPut.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $holdid = (int)($args['id'] ?? 0);

        if ($holdid <= 0) {
            return DefaultService::badRequest($request, $response);
        }

        if (!in_array($data->tag, ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect', 'text'])) {
            return DefaultService::badRequest($request, $response);
        }

        $hold = Hold::load($this->pdo, $holdid);
        if (is_null($hold)) {
            return DefaultService::notFound($request, $response);
        }

        $hold->tag = $data->tag;
        $hold->attr = $data->attr;
        if (!$hold->save($this->pdo)) {
            return DefaultService::internalServerError($request, $response);
        }

        return $response
            ->withStatus(204, 'No Content')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    public function deleteHold(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        /** @var User $user */
        $user = $request->getAttribute('user');

        if ($user->status < 2) {
            return DefaultService::forbidden($request, $response);
        }

        $holdid = (int)($args['id'] ?? 0);

        if ($holdid <= 0) {
            return DefaultService::badRequest($request, $response);
        }

        $hold = Hold::load($this->pdo, $holdid);
        if (is_null($hold)) {
            return DefaultService::notFound($request, $response);
        }

        $stmt = $this->pdo->prepare('DELETE FROM hold WHERE id = ?');
        if (!$stmt->execute([$hold->id])) {
            return DefaultService::internalServerError($request, $response);
        }

        return $response
            ->withStatus(204, 'No Content')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }
}