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
use Systemboard\PublicEntity\ChangeLogEntry as PublicChangeLogEntry;
use Systemboard\PublicEntity\SystemStats as PublicSystemStats;

class StatsService extends AbstractService
{
    public function get(Request $request, Response $response, $args)
    {
        if ($request->getAttribute('role') != 'user' && $request->getAttribute('role') != 'guest') {
            return DefaultService::forbidden($request, $response);
        }

        $stats = new PublicSystemStats();
        $stats->version = 'Systemboard v4.0.0-202004232005'; // todo adjust on release
        $stats->changelog = self::getChangeLog();

        $stmt = $this->pdo->prepare('SELECT (SELECT COUNT(*)
            FROM boulder_meta bm
            WHERE EXISTS(SELECT b.boulderid
                         FROM boulder b
                                  LEFT JOIN hold h ON b.holdid = h.id
                                  LEFT JOIN wall_segment ws on h.wall_segment = ws.id
                         WHERE ws.wall = (SELECT MAX(w.id) FROM wall w)
                           AND b.boulderid = bm.id)),
           (SELECT COUNT(*)
            FROM hold h
            WHERE h.wall_segment IN (SELECT ws.id
                                     FROM wall_segment ws
                                     WHERE ws.wall = (SELECT MAX(w.id) FROM wall w))),
           (SELECT COUNT(*) FROM user);');

        if ($stmt->execute() && ($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$stats->boulder, $stats->holds, $stats->users] = $row;
        } else {
            return DefaultService::internalServerError($request, $response);
        }

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withStatus(200, 'OK')
            ->withHeader('Content-Type', 'application/json; charset=utf8');
    }

    private static function getChangeLog(): array
    {
        $raw = [
            [
                'version' => 'v4.0.0',
                'date' => '16.12.2019', // todo adjust on release
                'description' => 'Corona Relaunch',
                'changes' => [
                    '+ Gastanmeldung',
                    '* Vollständig überarbeitete Oberfläche',
                    '* Vollständig überabeitetes Backend'
                ]
            ],
            [
                'version' => 'v3.0.5',
                'date' => '16.12.2019',
                'description' => 'Fehlerbehebung',
                'changes' => [
                    '* Ein Fehler in der Suche bei eingeschränkten Schwierigkeitsgraden wurde behoben'
                ]
            ],
            [
                'version' => 'v3.0.4',
                'date' => '06.10.2019',
                'description' => 'Fehlerbehebungen',
                'changes' => [
                    '* Die Eingabe von ungültigen Zugangsdaten führt nicht mehr dazu, dass weitere Loginversuche fehlschlagen'
                ]
            ],
            [
                'version' => 'v3.0.3',
                'date' => '21.08.2019',
                'description' => 'Kleines Update',
                'changes' => [
                    '* Die Wand für das Wintersemester 19/20 ist nun eingetragen',
                    '* Nun tatsächlich: Läuft die Session ab, so erscheint das Loginformular und die Seite muss nicht neugeladen werden'
                ]
            ],
            [
                'version' => 'v3.0.2',
                'date' => '12.05.2018',
                'description' => 'Kleines Update',
                'changes' => [
                    '+ Läuft die Session ab, so erscheint das Loginformular und die Seite muss nicht neugeladen werden',
                    '* Fehler in der Suche behoben'
                ]
            ],
            [
                'version' => 'v3.0.1',
                'date' => '06.05.2018',
                'description' => 'Fehlerbehebung beim Boulder des Tages',
                'changes' => []
            ],
            [
                'version' => 'v3.0.0',
                'date' => '05.05.2018',
                'description' => 'Großes Update der Systemarchitektur, durch welche neue Funktionen einfacher hinzugefügt werden können.',
                'changes' => [
                    '+ Boulder des Tages',
                    '+ Passwort vergessen',
                    '+ Instanzstatistiken und Informationen',
                    '* Systemarchitektur umgestellt',
                    '* Code aufgeräumt',
                    '* Kleinere Fehlerbehebungen und Verbesserungen'
                ]
            ],
            [
                'version' => 'v2.0.1',
                'date' => '04.02.2018',
                'description' => 'Fehlerbehebung der Suche',
                'changes' => [
                    '+ Sortierung der Suche nun nach Schwierigkeit möglich',
                    '* Fehler in der Suchfunktion behebt'
                ]
            ],
            [
                'version' => 'v2.0.0',
                'date' => '14.12.2017',
                'description' => 'Grafikupdate',
                'changes' => [
                    '+ Bilder statt Vektorgrafiken',
                    '+ Grafischer Editor beschleunigt das Eintragen von neuen Griffen enorm',
                    '* Sehr viele kleine Fehlerbehebungen'
                ]
            ]
        ];
        $changelog = [];
        foreach ($raw as $rawEntry) {
            $entry = new PublicChangeLogEntry();
            $entry->version = $rawEntry['version'];
            $entry->date = $rawEntry['date'];
            $entry->description = $rawEntry['description'];
            $entry->changes = $rawEntry['changes'];
            $changelog[] = $entry;
        }
        return $changelog;
    }
}