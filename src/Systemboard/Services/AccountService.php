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


use Exception;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Systemboard\Entity\User;
use Systemboard\PublicEntity\Token as TokenPublic;

class AccountService extends AbstractService
{
    public function login(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'login') {
            return DefaultService::forbidden($request, $response);
        }

        $email = (string) ($args['email'] ?? '');
        $authtype = (string) ($args['authtype'] ?? 'password');
        $auth = (string) ($request->getQueryParams()['auth'] ?? '');

        if (!in_array($authtype, ['password'])) {
            return DefaultService::notImplemented($request, $response);
        }

        if (mt_rand(0, 10) == 0) $this->gc();

        $token = new TokenPublic();
        if ($authtype == 'password') {
            $user = User::loadByEmail($this->pdo, $email);
            if (is_null($user) || $user->status == 0) {
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
                    $user->password = $hash;
                    $user->save($this->pdo);
                }
            } else {
                if (!password_verify($auth, $user->password)) {
                    return DefaultService::notFound($request, $response);
                }
                // rehash
                if (password_needs_rehash($user->password, PASSWORD_ARGON2I, ARGON_SETTINGS)
                    && $hash = password_hash($auth, PASSWORD_ARGON2I, ARGON_SETTINGS)) {
                    $user->password = $hash;
                    $user->save($this->pdo);
                }
            }
            $token->token = $this->createSession($user);
            $token->privileged = $user->status > 1;
        }

        if (empty($token->token)) {
            return DefaultService::internalServerError($request, $response);
        }

        $response->getBody()->write(json_encode($token));

        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    private function gc()
    {
        $stmt = $this->pdo->prepare('DELETE FROM session WHERE expires < NOW()');
        $stmt->execute([]);
    }

    private function createSession(User $user): string
    {
        $stmt = $this->pdo->prepare('INSERT INTO session (id, user, expires) VALUES (?, ?, ?)');
        for ($i = 0; $i < 3; $i++) {
            // Try at most 3 times
            try {
                $token = base64_encode(random_bytes(189));
                if ($stmt->execute([$token, $user->id, date('Y-m-d H:i:s', time() + SESSION_DURATION)]) && $stmt->rowCount() > 0) {
                    return $token;
                }
            } catch (Exception $e) {
                // todo implement handling
            }
        }
        return '';
    }

    public function register(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/registrationPost.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $password = password_hash($data->password, PASSWORD_ARGON2I, ARGON_SETTINGS);
        try {
            $activation = bin2hex(random_bytes(30));
        } catch (Exception $exception) {
            return DefaultService::internalServerError($request, $response);
        }
        $user = User::new($this->pdo, $data->email, $password, $data->name, 0, $activation, 0);
        if (is_null($user)) {
            return DefaultService::badRequest($request, $response);
        }

        $link = BASE_URL . '/?activation=' . urlencode($activation);
        $content = <<<CONTENT
Hallo $user->name,

du kannst deine Registrierung für das digitale Bouldersystem über den folgenden Link abschließen:
$link

Sollte jemand deine E-Mailadresse missbräulich verwendet haben, brauchst du nichts weiter unternehmen. Du wirst in diesem Fall keine weitere E-Mail erhalten.

Viele Grüße,
dein Bouldersystem
CONTENT;

        if (!mail($user->email, "Digitales Bouldersystem: Registrierung", $content, "From: Digitales Bouldersystem <systemboard@digitalbread.de>\r\nContent-Type: text/plain; charset=UTF-8")) {
            return DefaultService::internalServerError($request, $response);
        }

        return $response->withStatus(204, 'No Content');
    }

    public function activate(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/tokenPost.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $user = User::loadByActivation($this->pdo, $data->token);
        if (is_null($user)) {
            return DefaultService::badRequest($request, $response);
        }

        $user->status = 1;
        $user->save($this->pdo);

        return $response->withStatus(204, 'No Content');
    }

    public function pwReset(Request $request, Response $response, $args)
    {
        $email = (string)($args['email'] ?? '');

        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/tokenPost.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $user = User::loadByEmail($this->pdo, $email);
        if (is_null($user) || !empty($user->forgotpw)) {
            return DefaultService::badRequest($request, $response);
        }

        try {
            $user->forgotpw = bin2hex(random_bytes(50)); // todo error handling
        } catch (Exception $exception) {
            return DefaultService::internalServerError($request, $response);
        }
        $user->save($this->pdo);

        $forgotLink = BASE_URL . '/?forgotPw=' . $user->forgotpw;
        $misuseLink = BASE_URL . '/?enableForgotPw=' . $user->forgotpw;

        $content = <<<MAIL
Hallo $user->name,

jemand hat die Passwort-Vergessen Funktion des Digitalen Bouldersystems für deinen Account benutzt.

Falls du dies NICHT gewesen bist, ist diese Funktion für deinen Account aus Sicherheitsgründen ab sofort deaktiviert.
Falls du dein Passwort vergessen hast und ein neues Passwort setzen möchtest, klicke auf den folgenden Link: 
$forgotLink

Falls du zwar kein neues Passwort angefordert hast, aber die Funktion wieder aktivieren möchtest, klicke auf den folgenden Link:
$misuseLink

Viele Grüße,
dein Bouldersystem
MAIL;

        mail($email, "Digitales Bouldersystem: Passwort vergessen", $content, "From: Verwaltung Bouldersystem <systemboard@digitalbread.de>\r\nContent-Type: text/plain; charset=UTF-8");

        return $response->withStatus(204, 'No Content');
    }

    public function pwResetMisuse(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/tokenPost.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        User::pwResetMisuse($this->pdo, $data->token);

        return $response->withStatus(204, 'No Content');
    }

    public function newPassword(Request $request, Response $response, $args)
    {
        $token = (string)($args['token'] ?? '');

        $data = json_decode($request->getBody()->getContents());
        $schema = Schema::fromJsonString(file_get_contents('./schema/passwordPut.schema.json'));
        $validator = new Validator();
        $result = $validator->schemaValidation($data, $schema);

        if (!$result->isValid()) {
            return DefaultService::badRequest($request, $response);
        }

        $user = User::loadByForgotPw($this->pdo, $token);
        if (is_null($user)) {
            return DefaultService::badRequest($request, $response);
        }

        $user->password = password_hash($data->password, PASSWORD_ARGON2I, ARGON_SETTINGS);
        $user->forgotpw = null;
        $user->save($this->pdo);

        return $response->withStatus(204, 'No Content');
    }

    public function logout(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user') {
            return DefaultService::forbidden($request, $response);
        }

        $sessionId = $request->getAttribute('sessionId');
        if (!$sessionId) {
            return DefaultService::badRequest($request, $response);
        }

        $stmt = $this->pdo->prepare('DELETE FROM session WHERE id = ?');
        if ($stmt->execute([$sessionId])) {
            return $response->withStatus(200, 'OK');
        } else {
            return DefaultService::internalServerError($request, $response);
        }
    }
}