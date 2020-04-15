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

class WallSegment extends AbstractEntity
{
    public int $id;
    public ?Wall $wall;
    public string $filename;

    private function __construct(int $id, ?Wall $wall = null, ?string $filename = null)
    {
        $this->id = $id;
        $this->wall = $wall;
        $this->filename = $filename ?? '';

        $this->resolved = !is_null($wall) && !is_null($filename);
    }

    public static function load(PDO $pdo, int $id): ?WallSegment
    {
        $stmt = $pdo->prepare('SELECT id, wall, filename FROM wall_segment WHERE id = ?');
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            [$id, $wallId, $filename] = $stmt->fetch(PDO::FETCH_NUM);
            return new WallSegment($id, Wall::unresolved($wallId), $filename);
        }
        return null;
    }

    /**
     * @param PDO  $pdo
     * @param Wall $wall
     *
     * @return WallSegment[]
     */
    public static function loadByWall(PDO $pdo, Wall $wall): array
    {
        $segments = [];
        $stmt = $pdo->prepare('SELECT id, wall, filename FROM wall_segment WHERE wall = ?');
        if ($stmt->execute([$wall->id])) {

            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                [$id, $wallId, $filename] = $row;
                $segments[] = new WallSegment($id, Wall::unresolved($wallId), $filename);
            }

        }
        return $segments;
    }

    public static function unresolved(int $id): WallSegment
    {
        return new WallSegment($id);
    }

    public function resolve(PDO $pdo): bool
    {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, wall, filename FROM wall_segment WHERE id = ?');
            if ($stmt->execute([$this->id]) && $stmt->rowCount() > 0) {
                [$this->id, $wallId, $this->filename] = $stmt->fetch(PDO::FETCH_NUM);
                $this->wall = Wall::unresolved($wallId);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }
}
