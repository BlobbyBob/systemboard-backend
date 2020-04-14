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

class HelloService
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(Request $request, Response $response, $args)
    {
        $response->getBody()->write("Hello World!\n");

        $stmt = $this->pdo->query('SELECT * FROM user WHERE id = 1');
        $response->getBody()->write(print_r($stmt->fetch(), true));
        return $response->withHeader("Content-Type", "text/plain");
    }
}