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


namespace Systemboard\Entity;


use PDO;

class User extends AbstractEntity
{
    public int $id;
    public string $email;
    public string $password;
    public string $name;
    public int $status;
    public ?string $activation;
    public int $newsletter;
    public ?string $forgotpw;
    public ?string $badge;


    /**
     * User constructor.
     *
     * @param int         $id
     * @param string|null $email
     * @param string|null $password
     * @param string|null $name
     * @param int|null    $status
     * @param string|null $activation
     * @param int|null    $newsletter
     * @param string|null $forgotpw
     * @param string|null $badge
     */
    private function __construct(int $id, ?string $email = null, ?string $password = null, ?string $name = null, ?int $status = null,
                                 ?string $activation = null, ?int $newsletter = null, ?string $forgotpw = null, ?string $badge = null)
    {
        $this->id = $id;
        $this->email = $email ?? '';
        $this->password = $password ?? '';
        $this->name = $name ?? '';
        $this->status = $status ?? 0;
        $this->activation = $activation;
        $this->newsletter = $newsletter ?? 0;
        $this->forgotpw = $forgotpw;
        $this->badge = $badge;

        $this->resolved = !is_null($email) && !is_null($password) && !is_null($name) && !is_null($status) && !is_null($newsletter);
    }

    public static function load(PDO $pdo, int $id): ?User
    {
        $stmt = $pdo->prepare('SELECT id, email, password, name, status, activation, newsletter, forgotpw, badge FROM user WHERE id = ?');
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            [$id, $email, $password, $name, $status, $activation, $newsletter, $forgotpw, $badge] = $stmt->fetch(PDO::FETCH_NUM);
            return new User($id, $email, $password, $name, $status, $activation, $newsletter, $forgotpw, $badge);
        }
        return null;
    }

    public static function unresolved(int $id)
    {
        return new User($id);
    }

    public function resolve(PDO $pdo): bool
    {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, email, password, name, status, activation, newsletter, forgotpw, badge FROM user WHERE id = ?');
            if ($stmt->execute([$this->id]) && $stmt->rowCount() > 0) {
                [$this->id, $this->email, $this->password, $this->name, $this->status, $this->activation, $this->newsletter, $this->forgotpw, $this->badge] = $stmt->fetch(PDO::FETCH_NUM);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }

}