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


namespace Systemboard\Middleware;


use PDO;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Headers;
use Slim\Psr7\Response;
use Systemboard\Entity\User;
use Systemboard\Services\DefaultService;

class Authentication implements MiddlewareInterface
{
    private PDO $pdo;

    /**
     * Authentication constructor.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        if (strtoupper($request->getMethod()) == 'OPTIONS') {
            return new Response(200, new Headers(["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Method" => "*", "Access-Control-Allow-Headers" => "*"]));
        }

        $header = $request->getHeader('Authorization');
        if (count($header) == 0) {
            // No authentication is not allowed
            $response = new Response();
            return $response->withStatus(401, 'Unauthorized');
        }

        $parts = explode(' ', $header[0]);

        if (count($parts) == 0) {
            // No authentication is not allowed
            $response = new Response();
            return $response->withStatus(401, 'Unauthorized');
        } else if (strtolower($parts[0]) == 'guest') {

            $request = $request->withAttribute('role', 'guest');

        } else if (strtolower($parts[0]) == 'bearer' && count($parts) == 2) {

            $stmt = $this->pdo->prepare('SELECT user FROM session WHERE id = ?');
            if ($stmt->execute([$parts[1]]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $stmtRefresh = $this->pdo->prepare('UPDATE session SET expires = ? WHERE id = ?');
                $stmtRefresh->execute([date('Y-m-d H:i:s', time() + SESSION_DURATION), $parts[1]]);
                $request = $request->withAttribute('login', true);
                [$userid] = $row;
                $user = User::load($this->pdo, $userid);
                if ($user == null) {
                    $response = new Response();
                    return $response->withStatus(500, 'Internal Server Error');
                }
                $request = $request->withAttribute('sessionId', $parts[1]);
                $request = $request->withAttribute('role', 'user');
                $request = $request->withAttribute('user', $user);
            } else {
                return (new Response())->withStatus(401, 'Unauthorized');
            }

        } else if (strtolower($parts[0]) == 'login') {

            $request = $request->withAttribute('role', 'login');

        } else {
            // Unknown authentication scheme
            $response = new Response();
            return $response->withStatus(401, 'Unauthorized');
        }

        return $handler->handle($request);
    }
}