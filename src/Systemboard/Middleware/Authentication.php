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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Systemboard\Entity\User;

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
        $header = $request->getHeader('Authorization');
        if (count($header) == 0) {
            // No authentication is not allowed
            $response = new Response();
            return $response
                ->withStatus(401, 'Unauthorized');
        }

        $parts = explode(' ', $header[0]);

        if (count($parts) == 0) {
            // No authentication is not allowed
            $response = new Response();
            return $response
                ->withStatus(401, 'Unauthorized');
        } else if (strtolower($parts[0]) == 'guest') {

            $request = $request->withAttribute('role', 'guest');

        } else if (strtolower($parts[0]) == 'bearer' && count($parts) == 2) {

            // todo refresh session duration
            $stmt = $this->pdo->prepare('SELECT user FROM session WHERE id = ?');
            if ($stmt->execute([$parts[1]]) && $stmt->rowCount() > 0) {
                $request = $request->withAttribute('login', true);
                [$userid] = $stmt->fetch(PDO::FETCH_NUM);
                $user = User::load($this->pdo, $userid);
                if ($user == null) {
                    $response = new Response();
                    return $response
                        ->withStatus(500, 'Internal Server Error');
                }
                $request = $request->withAttribute('role', 'user');
                $request = $request->withAttribute('user', $user);
            }

        } else if (strtolower($parts[0]) == 'login') {

            $request = $request->withAttribute('role', 'login');

        } else {
            // Unknown authentication scheme
            $response = new Response();
            return $response
                ->withStatus(401, 'Unauthorized');
        }

        return $handler->handle($request);
    }
}