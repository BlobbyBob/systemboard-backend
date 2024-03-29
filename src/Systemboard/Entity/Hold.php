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
    /**
     * @var int
     */
    public $id;
    /**
     * @var WallSegment|null
     */
    public $wallSegment;
    /**
     * @var string
     */
    public $tag;
    /**
     * @var string
     */
    public $attr;


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
        if ($stmt->execute([$id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$id, $wallSegment, $tag, $attr] = $row;
            return new Hold($id, WallSegment::unresolved($wallSegment), $tag, $attr);
        }
        return null;
    }

    /**
     * @param PDO         $pdo
     * @param WallSegment $wallSegment
     *
     * @return Hold[]
     */
    public static function loadByWallSegment(PDO $pdo, WallSegment $wallSegment): array
    {
        $holds = [];
        $stmt = $pdo->prepare('SELECT id, tag, attr FROM hold WHERE wall_segment = ?');
        if ($stmt->execute([$wallSegment->id])) {
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                [$id, $tag, $attr] = $row;
                $holds[] = new Hold($id, $wallSegment, $tag, $attr);
            }
        }
        return $holds;
    }

    public static function create(PDO $pdo, int $wallSegment, string $tag, string $attr): ?Hold
    {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO hold (wall_segment, tag, attr) VALUES (?, ?, ?)');
        if ($stmt->execute([$wallSegment, $tag, $attr])) {
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return Hold::load($pdo, $id);
        }
        $pdo->rollBack();
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
            if ($stmt->execute([$this->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                [$this->id, $wallSegment, $this->tag, $this->attr] = $row;
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

    public function save(PDO $pdo): bool
    {
        if (!$this->resolved) return false;

        $stmt = $pdo->prepare('UPDATE hold SET tag = ?, attr = ? WHERE id = ?');
        return $stmt->execute([$this->tag, $this->attr, $this->id]);
    }

}
