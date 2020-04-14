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

class Wall extends AbstractEntity
{
    public int $id;
    public string $name;

    private function __construct(int $id, string $name = null)
    {
        $this->id = $id;
        $this->name = $name ?? '';

        $this->resolved = !is_null($name);
    }

    public static function load(PDO $pdo, int $id): Wall
    {
        $stmt = $pdo->prepare('SELECT id, name FROM wall WHERE id = ?');
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            list($id, $name) = $stmt->fetch(PDO::FETCH_NUM);
            return new Wall($id, $name);
        }
        return null;
    }

    public static function unresolved(int $id): Wall
    {
        return new Wall($id);
    }

    public function resolve(PDO $pdo): bool {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, name FROM wall WHERE id = ?');
            if ($stmt->execute([$this->id]) && $stmt->rowCount() > 0) {
                list($this->id, $this->name) = $stmt->fetch(PDO::FETCH_NUM);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }
}