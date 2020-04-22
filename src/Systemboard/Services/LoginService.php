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
use Systemboard\Entity\User;
use Systemboard\PublicEntity\Token as TokenPublic;

class LoginService extends AbstractService
{
    public function login(Request $request, Response $response, $args)
    {
        $email = (string) ($args['email'] ?? '');
        $authtype = (string) ($args['authtype'] ?? 'password');
        $auth = (string) ($request->getQueryParams()['auth'] ?? '');

        if (array_search($authtype, ['password', 'guest']) === false) {
            return DefaultService::notImplemented($request, $response);
        }

        $token = new TokenPublic();
        if ($authtype == 'password') {
            $user = User::loadByEmail($this->pdo, $email);
            if (is_null($user)) {
                return DefaultService::notFound($request, $response);
            }

            if ($user->password[0] != '$') {
                // legacy
                $hash = hash('sha256', $auth);
                if (!hash_equals($hash, $user->password)) {
                    return DefaultService::notFound($request, $response);
                }
                // rehash
                if ($hash = password_hash($auth, PASSWORD_ARGON2I, ARGON_SETTINGS)) {
                    $user->password = (string) $hash;
                    $user->save($this->pdo);
                }
            } else {
                if (!password_verify($auth, $user->password)) {
                    return DefaultService::notFound($request, $response);
                }
                // rehash
                if (password_needs_rehash($user->password, PASSWORD_ARGON2I, ARGON_SETTINGS)
                    && $hash = password_hash($auth, PASSWORD_ARGON2I, ARGON_SETTINGS)) {
                    $user->password = (string) $hash;
                    $user->save($this->pdo);
                }
            }
            $token->token = $this->session($user);
        }

        if ($authtype == 'guest') {
            $token->token = hash('sha256', 'guest');
        }

        $response->getBody()->write(json_encode($token));

        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    private function session(User $user): string
    {
        return '';
    }
}