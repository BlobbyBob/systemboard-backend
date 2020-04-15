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

class Boulder extends AbstractEntity
{
    public int $id;
    public string $name;
    public ?User $user;
    public ?string $description;
    public $date;


    /**
     * Boulder constructor.
     *
     * @param int         $id
     * @param string|null $name
     * @param User|null   $user
     * @param string|null $description
     * @param null        $date
     */
    private function __construct(int $id, string $name = null, ?User $user = null, ?string $description = null, $date = null)
    {
        $this->id = $id;
        $this->name = $name ?? '';
        $this->user = $user;
        $this->description = $description;
        $this->date = $date;

        $this->resolved = !is_null($name);
    }

    public static function load(PDO $pdo, int $id): ?Boulder
    {
        $stmt = $pdo->prepare('SELECT id, name, user, description, date FROM boulder_meta WHERE id = ?');
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            [$id, $name, $user, $description, $date] = $stmt->fetch(PDO::FETCH_NUM);
            return new Boulder($id, $name, User::unresolved($user), $description, $date);
        }
        return null;
    }

    public static function unresolved(int $id): Boulder
    {
        return new Boulder($id);
    }

    public function resolve(PDO $pdo): bool
    {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, name, user, description, date FROM boulder_meta WHERE id = ?');
            if ($stmt->execute([$this->id]) && $stmt->rowCount() > 0) {
                [$this->id, $this->name, $user, $this->description, $this->date] = $stmt->fetch(PDO::FETCH_NUM);
                $this->user = User::unresolved($user);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }

}
