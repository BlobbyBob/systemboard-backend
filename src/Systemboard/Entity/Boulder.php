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
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var User|null
     */
    public $user;
    /**
     * @var string|null
     */
    public $description;
    public $date;

    /**
     * @var float|null
     */
    private $cachedGrade;
    /**
     * @var float|null
     */
    private $cachedRating;

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

    public static function unresolved(int $id): Boulder
    {
        return new Boulder($id);
    }

    public static function create(PDO $pdo, string $name, User $user, string $description, array $holds): ?Boulder
    {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO boulder_meta (name, user, description) VALUES (?, ?, ?)');
        if ($stmt->execute([$name, $user->id, $description])) {
            $id = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO boulder (boulderid, holdid, type) VALUES (?, ?, ?)');
            foreach ($holds as [$holdid, $type]) {
                if (!$stmt->execute([$id, $holdid, $type])) {
                    $pdo->rollBack();
                    return null;
                }
            }
            $pdo->commit();
            return Boulder::load($pdo, $id);
        }
        $pdo->rollBack();
        return null;
    }

    public static function load(PDO $pdo, int $id): ?Boulder
    {
        $stmt = $pdo->prepare('SELECT id, name, user, description, date FROM boulder_meta WHERE id = ?');
        if ($stmt->execute([$id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$id, $name, $user, $description, $date] = $row;
            return new Boulder($id, $name, User::unresolved($user), $description, $date);
        }
        return null;
    }

    /**
     * @param PDO   $pdo
     * @param array $constraints
     * @param int   $limit
     *
     * @return Boulder[]
     */
    public static function search(PDO $pdo, array $constraints, int $limit = 18): array
    {
        // Preprocess constraints for sql's LIKE
        $constraints[1] = '%' . str_replace('%', '%%', $constraints[1]) . '%';
        $constraints[3] = '%' . str_replace('%', '%%', $constraints[3]) . '%';

        $boulder = [];

        $stmt = $pdo->prepare('SELECT MAX(id) FROM wall');
        $stmt->execute();
        $wallId = $stmt->fetchColumn();

        // Append wall id and limit
        $constraints[] = $wallId;
        $constraints[] = $limit;

        $stmt = $pdo->prepare('SELECT DISTINCT bm.id, bm.name, bm.user, u.name, bm.description, bm.date, gv.grade, rv.stars FROM boulder_meta bm 
            LEFT JOIN user u ON bm.user = u.id 
            LEFT JOIN grade_view gv ON bm.id = gv.boulder
            LEFT JOIN rating_view rv ON bm.id = rv.boulder 
            LEFT JOIN boulder b ON b.boulderid = bm.id LEFT JOIN hold h ON b.holdid = h.id LEFT JOIN wall_segment ws ON h.wall_segment = ws.id WHERE
            (? OR bm.name LIKE ?) AND
            (? OR u.name LIKE ?) AND
            (? OR bm.user = ?) AND
            (? OR gv.grade >= ?) AND
            (? OR gv.grade <= ?) AND
            (? OR rv.stars >= ?) AND
            (? OR rv.stars <= ?) AND
            (? OR NOT EXISTS(SELECT * FROM climbed c WHERE c.user = ? AND c.boulder = bm.id)) AND
            ws.wall = ?
            ORDER BY bm.id DESC
            LIMIT ?');
        if ($stmt->execute($constraints)) {
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                [$id, $name, $userid, $username, $description, $date, $grade, $rating] = $row;
                $user = User::unresolved($userid);
                $user->name = $username;
                $b = new Boulder($id, $name, $user, $description, $date);
                $b->cachedGrade = (float) $grade;
                $b->cachedRating = (float) $rating;
                $boulder[] = $b;
            }
        }

        return $boulder;
    }

    public static function boulderOfTheDay(PDO $pdo): int
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO boulder_of_the_day SELECT NULL, bm.id, CURRENT_DATE FROM boulder_meta bm 
            WHERE EXISTS (SELECT * FROM boulder b JOIN hold h ON b.holdid = h.id JOIN wall_segment ws ON h.wall_segment = ws.id 
                WHERE b.boulderid = bm.id AND ws.wall = (SELECT MAX(w.id) FROM wall w)) ORDER BY RAND(?) LIMIT 1");
        if (!$stmt->execute([time()])) {
            return -1;
        }

        $stmt = $pdo->prepare('SELECT boulderid FROM boulder_of_the_day WHERE `date` = (SELECT MAX(date) FROM boulder_of_the_day)');
        if ($stmt->execute()) {
            [$id] = $stmt->fetch(PDO::FETCH_NUM);
            return $id;
        }

        return -1;
    }

    public function resolve(PDO $pdo): bool
    {
        if (!$this->resolved) {
            $stmt = $pdo->prepare('SELECT id, name, user, description, date FROM boulder_meta WHERE id = ?');
            if ($stmt->execute([$this->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                [$this->id, $this->name, $user, $this->description, $this->date] = $row;
                $this->user = User::unresolved($user);
                $this->resolved = true;
            }
        }
        return $this->resolved;
    }

    /**
     * @param PDO $pdo
     *
     * @return array Each element consisting of [Hold, int], where the second parameter indicates the type of the hold
     */
    public function fetchHolds(PDO $pdo): array
    {
        $holds = [];
        $stmt = $pdo->prepare('SELECT holdid, type FROM boulder WHERE boulderid = ?');
        if ($stmt->execute([$this->id])) {
            while (([$holdid, $type] = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $holds[] = [Hold::unresolved($holdid), $type];
            }
        }
        return $holds;
    }

    /**
     * @param PDO $pdo
     *
     * @return User[]
     */
    public function fetchClimbers(PDO $pdo): array
    {
        $climbers = [];
        $stmt = $pdo->prepare('SELECT user FROM climbed WHERE boulder = ?');
        if ($stmt->execute([$this->id])) {
            while (([$userid] = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $climbers[] = User::unresolved($userid);
            }
        }
        return $climbers;
    }

    public function climbedBy(PDO $pdo, User $user): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM climbed WHERE user = ? AND boulder = ?');
        if ($stmt->execute([$user->id, $this->id]) && $stmt->fetchColumn() > 0) {
            return true;
        }
        return false;
    }

    public function isBoulderOfTheDay(PDO $pdo): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM boulder_of_the_day WHERE boulderid = ? AND date = CURRENT_DATE');
        if ($stmt->execute([$this->id])) {
            [$count] = $stmt->fetch(PDO::FETCH_NUM);
            if ($count > 0) return true;
        }
        return false;
    }

    public function fetchAscents(PDO $pdo): int
    {
        $stmt = $pdo->prepare('SELECT count FROM climbed_view WHERE boulder = ?');
        if ($stmt->execute([$this->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$count] = $row;
            return $count;
        }
        return 0;
    }

    public function getRating(PDO $pdo, bool $cached = false): ?float
    {
        if ($cached) return $this->cachedRating;

        $stmt = $pdo->prepare('SELECT stars FROM rating_view WHERE boulder = ?');
        if ($stmt->execute([$this->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$stars] = $row;
            return (float) $stars;
        }
        return null;
    }

    public function getGrade(PDO $pdo, bool $cached = false): ?float
    {
        if ($cached) return $this->cachedGrade;

        $stmt = $pdo->prepare('SELECT grade FROM grade_view WHERE boulder = ?');
        if ($stmt->execute([$this->id]) && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$grade] = $row;
            return (float) $grade;
        }
        return null;
    }

    public function save(PDO $pdo): bool
    {
        if (!$this->resolved) return false;

        $stmt = $pdo->prepare('UPDATE boulder_meta SET name = ?, description = ?, user = ?, date = ? WHERE id = ?');
        return $stmt->execute([$this->name, $this->description, $this->user->id, $this->date, $this->id]);
    }

}
