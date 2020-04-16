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

class Hold extends AbstractEntity
{
    public int $id;
    public ?WallSegment $wallSegment;
    public string $tag;
    public string $attr;


    /**
     * Hold constructor.
     *
     * @param int              $id
     * @param WallSegment|null $wallSegment
     * @param string|null      $tag
     * @param string|null      $attr
     */
    private function __construct(int $id, ?WallSegment $wallSegment = null, ?string $tag = null, ?string $attr = null)
    {
        $this->id = $id;
        $this->wallSegment = $wallSegment;
        $this->tag = $tag ?? '';
        $this->attr = $attr ?? '';

        $this->resolved = !is_null($tag) && !is_null($attr);
    }

    public static function load(PDO $pdo, int $id): ?Hold
    {
        $stmt = $pdo->prepare('SELECT id, wall_segment, tag, attr FROM hold WHERE id = ?');
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            [$id, $wallSegment, $tag, $attr] = $stmt->fetch(PDO::FETCH_NUM);
            return new Hold($id, WallSegment::unresolved($wallSegment), $tag, $attr);
        }
        return null;
    }

    public static function unresolved(int $id): Hold
    {
        return new Hold($id);
    }

    public function resolve(PDO $pdo): bool
    {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, wall_segment, tag, attr FROM hold WHERE id = ?');
            if ($stmt->execute([$this->id]) && $stmt->rowCount() > 0) {
                [$this->id, $wallSegment, $this->tag, $this->attr] = $stmt->fetch(PDO::FETCH_NUM);
                $this->wallSegment = WallSegment::unresolved($wallSegment);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }

    /**
     * @param PDO $pdo
     *
     * @return Boulder[]
     */
    public function fetchBoulders(PDO $pdo): array
    {
        $holds = [];
        $stmt = $pdo->prepare('SELECT boulderid FROM boulder WHERE holdid = ?');
        if ($stmt->execute([$this->id])) {
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $holds[] = Boulder::unresolved($row[0]);
            }
        }
        return $holds;
    }

}
