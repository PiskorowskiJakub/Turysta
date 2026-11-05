<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;
use PDO;
use Turysta\Admin\Paging;
use Turysta\Game\Clock;

/** Widoki oddzialow dla gracza: katalog, profil publiczny, panel czlonka oraz zaproszenia. */
final class SquadView
{
    public const PER_PAGE = 20;

    public function __construct(private PDO $pdo, private SquadService $service)
    {
    }

    /** Dane do formularzy: specjalizacje, role i limity. @return array<string,mixed> */
    public function meta(): array
    {
        return [
            'specializations' => SquadRules::specializations(),
            'roles' => array_keys(SquadRules::ROLE_LIMITS),
            'assignable_roles' => SquadRules::ASSIGNABLE_ROLES,
            'kick_reasons' => SquadRules::KICK_REASONS,
            'report_reasons' => SquadRules::REPORT_REASONS,
            'limits' => [
                'max_members' => SquadRules::MAX_MEMBERS,
                'min_account_days' => SquadRules::MIN_ACCOUNT_DAYS,
                'rejoin_cooldown_days' => SquadRules::REJOIN_COOLDOWN_DAYS,
                'name_min' => SquadRules::NAME_MIN,
                'name_max' => SquadRules::NAME_MAX,
                'name_change_days' => SquadRules::NAME_CHANGE_DAYS,
                'spec_change_days' => SquadRules::SPEC_CHANGE_DAYS,
                'recruit_days' => SquadRules::RECRUIT_DAYS,
                'fire_threshold_percent' => SquadRules::FIRE_THRESHOLD_PERCENT,
                'fire_min_active' => SquadRules::FIRE_MIN_ACTIVE,
                'flame_streaks' => SquadRules::FLAME_STREAKS,
            ],
        ];
    }

    /**
     * Katalog oddzialow z filtrem po specjalizacji i nazwie.
     *
     * @param array<string,mixed> $query spec, q, page
     * @return array<string,mixed>
     */
    public function catalog(array $query): array
    {
        $where = ['s.archived_at IS NULL'];
        $params = [];
        $spec = is_string($query['spec'] ?? null) ? $query['spec'] : '';
        if ($spec !== '' && isset(SquadRules::SPECIALIZATIONS[$spec])) {
            $where[] = 's.specialization = ?';
            $params[] = $spec;
        }
        $q = is_string($query['q'] ?? null) ? trim($query['q']) : '';
        if ($q !== '') {
            $where[] = 's.name LIKE ?';
            $params[] = Paging::like(mb_substr($q, 0, 40));
        }
        $w = implode(' AND ', $where);
        $result = Paging::run(
            $this->pdo,
            "SELECT COUNT(*) FROM squads s WHERE $w",
            "SELECT s.*, (SELECT COUNT(*) FROM squad_members m WHERE m.squad_id = s.id) AS members
             FROM squads s WHERE $w ORDER BY members DESC, s.id ASC",
            $params,
            max(1, (int) ($query['page'] ?? 1)),
            fn(array $r): array => $this->card($r),
            self::PER_PAGE
        );
        return ['squads' => $result['rows'], 'total' => $result['total'], 'page' => $result['page'], 'pages' => $result['pages']];
    }

    /** @param array<string,mixed> $r wiersz squads z kolumna members @return array<string,mixed> */
    private function card(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'display_name' => SquadRules::displayName((string) $r['name'], (string) $r['specialization']),
            'specialization' => (string) $r['specialization'],
            'join_mode' => (string) $r['join_mode'],
            'members' => (int) $r['members'],
            'max_members' => SquadRules::MAX_MEMBERS,
            'flame_level' => SquadFire::level((int) $r['flame_streak']),
        ];
    }

    /**
     * Profil oddzialu. Osoba spoza oddzialu widzi nazwe, specjalizacje, liczbe czlonkow, odznaki i liste czlonkow;
     * czlonek dodatkowo kronike, ognisko, zgloszenia i uprawnienia.
     *
     * @param array<string,mixed> $viewer wiersz users
     * @return array<string,mixed>
     */
    public function profile(array $viewer, int $squadId): array
    {
        $squad = $this->service->squad($squadId);
        $viewerId = (int) $viewer['id'];
        $mine = $this->service->memberRow($viewerId);
        $isMember = $mine !== null && (int) $mine['squad_id'] === $squadId;
        $myRole = $isMember ? (string) $mine['role'] : null;

        $members = $this->members($squadId, $isMember);
        $out = $this->card($squad + ['members' => count($members)]) + [
            'created_at' => Clock::iso((string) $squad['created_at']),
            'name_locked' => (int) $squad['name_locked'] === 1,
            'name_forced' => (int) $squad['name_forced'] === 1,
            'badges' => [],
            'member_list' => $members,
            'me' => [
                'is_member' => $isMember,
                'role' => $myRole,
                'can_manage' => in_array($myRole, ['leader', 'deputy'], true),
                'can_recruit' => in_array($myRole, ['leader', 'deputy', 'recruiter'], true),
                'is_leader' => $myRole === 'leader',
                'eligibility' => $isMember ? null : $this->service->eligibility($viewer),
                'pending' => $this->pendingKind($viewerId, $squadId),
            ],
        ];
        if ($isMember) {
            $out['name_change_available_at'] = $this->availableAt($squad['name_changed_at'], SquadRules::NAME_CHANGE_DAYS, (int) $squad['name_forced'] === 1);
            $out['spec_change_available_at'] = $this->availableAt($squad['spec_changed_at'], SquadRules::SPEC_CHANGE_DAYS, false);
            $out['fire'] = $this->fire($squad, $members);
            $out['chronicle'] = SquadChronicle::list($this->pdo, $squadId, $viewerId);
            if (in_array($myRole, ['leader', 'deputy', 'recruiter'], true)) {
                $out['requests'] = $this->requests($squadId);
            }
        }
        return $out;
    }

    /** Czy i do kiedy gracz ma zgloszenie albo zaproszenie do oddzialu: 'request', 'invite' albo null. */
    private function pendingKind(int $userId, int $squadId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT kind FROM squad_requests WHERE squad_id = ? AND user_id = ?');
        $stmt->execute([$squadId, $userId]);
        $k = $stmt->fetchColumn();
        return $k === false ? null : (string) $k;
    }

    private function availableAt(mixed $changedAt, int $days, bool $forced): ?string
    {
        if ($forced || $changedAt === null) {
            return null;
        }
        $at = Clock::parse((string) $changedAt)->modify("+$days days");
        return $at > Clock::now($this->pdo) ? Clock::iso(Clock::fmt($at)) : null;
    }

    /**
     * Lista czlonkow. Tytul roli widoczny dla obcych tylko, gdy gracz go nie wylaczyl; czlonkowie oddzialu widza wszystkie role.
     *
     * @return list<array<string,mixed>>
     */
    private function members(int $squadId, bool $viewerIsMember): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.user_id, m.role, m.joined_at, u.character_name, av.image_url, st.last_active_day,
                    EXISTS(SELECT 1 FROM sessions s WHERE s.user_id = u.id AND s.last_activity > UTC_TIMESTAMP() - INTERVAL 5 MINUTE) AS online
             FROM squad_members m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN avatars av ON av.id = u.avatar_id
             LEFT JOIN user_stats st ON st.user_id = u.id
             WHERE m.squad_id = ?"
        );
        $stmt->execute([$squadId]);
        $now = Clock::now($this->pdo);
        $order = ['leader' => 0, 'deputy' => 1, 'guide' => 2, 'quartermaster' => 3, 'chronicler' => 4, 'recruiter' => 5, 'member' => 6];
        $rows = $stmt->fetchAll();
        usort($rows, static fn(array $a, array $b): int => ($order[$a['role']] <=> $order[$b['role']]) ?: strcasecmp((string) $a['character_name'], (string) $b['character_name']));

        $out = [];
        foreach ($rows as $r) {
            $joined = Clock::parse((string) $r['joined_at']);
            $entry = [
                'id' => (int) $r['user_id'],
                'name' => (string) $r['character_name'],
                'avatar_url' => $r['image_url'],
                'title' => $r['role'] !== 'member' ? (string) $r['role'] : null,
                'joined_at' => Clock::iso((string) $r['joined_at']),
                'recruit' => $joined->modify('+' . SquadRules::RECRUIT_DAYS . ' days') > $now,
            ];
            if ($viewerIsMember) {
                $entry['role'] = (string) $r['role'];
                $entry['online'] = (bool) $r['online'];
            }
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Ognisko: poziom plomienia, zar, seria i postep dzisiejszego dnia oraz ostatnie dni.
     *
     * @param array<string,mixed> $squad
     * @param list<array<string,mixed>> $members
     * @return array<string,mixed>
     */
    private function fire(array $squad, array $members): array
    {
        $today = Clock::gameDay(Clock::now($this->pdo));
        [$count, $active] = SquadFire::countFor($this->pdo, (int) $squad['id'], $today);
        $needed = SquadFire::needed($count);
        $streak = (int) $squad['flame_streak'];
        $level = SquadFire::level($streak);

        $stmt = $this->pdo->prepare('SELECT day, active, members, needed, lit, ember FROM squad_fire_days WHERE squad_id = ? ORDER BY day DESC LIMIT 14');
        $stmt->execute([(int) $squad['id']]);
        $history = [];
        foreach ($stmt->fetchAll() as $d) {
            $history[] = [
                'day' => (string) $d['day'],
                'members' => (int) $d['members'],
                'active' => (int) $d['active'],
                'needed' => (int) $d['needed'],
                'lit' => (int) $d['lit'] === 1,
                'ember' => (int) $d['ember'],
            ];
        }
        $next = null;
        foreach (SquadRules::FLAME_STREAKS as $i => $min) {
            if ($min > $streak) {
                $next = ['level' => $i, 'in_days' => $min - $streak];
                break;
            }
        }
        return [
            'level' => $level,
            'streak' => $streak,
            'best_streak' => (int) $squad['best_streak'],
            'lit_days' => (int) $squad['lit_days'],
            'ember' => (int) $squad['ember'],
            // Przyrost zaru z ostatniego rozliczonego dnia (0, gdy ognisko wtedy nie plonelo).
            'ember_gain' => $history ? $history[0]['ember'] : 0,
            'next_level' => $next,
            'today' => ['day' => $today, 'members' => $count, 'active' => $active, 'needed' => $needed, 'lit' => $count >= SquadRules::FIRE_MIN_ACTIVE && $active >= $needed],
            'history' => $history,
        ];
    }

    /** Zgloszenia graczy i wyslane zaproszenia (dla uprawnionych). @return array<string,mixed> */
    private function requests(int $squadId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.kind, r.user_id, r.created_at, u.character_name FROM squad_requests r JOIN users u ON u.id = r.user_id
             WHERE r.squad_id = ? ORDER BY r.created_at'
        );
        $stmt->execute([$squadId]);
        $requests = [];
        $invites = [];
        foreach ($stmt->fetchAll() as $r) {
            $row = ['id' => (int) $r['user_id'], 'name' => (string) $r['character_name'], 'created_at' => Clock::iso((string) $r['created_at'])];
            if ($r['kind'] === 'request') {
                $requests[] = $row;
            } else {
                $invites[] = $row;
            }
        }
        return ['requests' => $requests, 'invites' => $invites];
    }

    /**
     * Przeglad dla gracza: jego oddzial (jesli jest), zaproszenia, zgloszenia i mozliwosc zalozenia/dolaczenia.
     *
     * @param array<string,mixed> $viewer
     * @return array<string,mixed>
     */
    public function mine(array $viewer): array
    {
        $uid = (int) $viewer['id'];
        $m = $this->service->memberRow($uid);
        $stmt = $this->pdo->prepare(
            "SELECT r.kind, s.id, s.name, s.specialization, s.join_mode, s.flame_streak,
                    (SELECT COUNT(*) FROM squad_members mm WHERE mm.squad_id = s.id) AS members
             FROM squad_requests r JOIN squads s ON s.id = r.squad_id AND s.archived_at IS NULL
             WHERE r.user_id = ? ORDER BY r.created_at"
        );
        $stmt->execute([$uid]);
        $invites = [];
        $requests = [];
        foreach ($stmt->fetchAll() as $r) {
            $card = $this->card($r);
            if ($r['kind'] === 'invite') {
                $invites[] = $card;
            } else {
                $requests[] = $card;
            }
        }
        return [
            'squad' => $m ? $this->profile($viewer, (int) $m['squad_id']) : null,
            'invites' => $invites,
            'requests' => $requests,
            'eligibility' => $this->service->eligibility($viewer),
            'meta' => $this->meta(),
        ];
    }

    /** Krotki opis oddzialu gracza do profilu publicznego. @return array<string,mixed>|null */
    public function badgeFor(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name, s.specialization, m.role, u.show_squad_title FROM squad_members m
             JOIN squads s ON s.id = m.squad_id JOIN users u ON u.id = m.user_id WHERE m.user_id = ? AND s.archived_at IS NULL'
        );
        $stmt->execute([$userId]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }
        return [
            'id' => (int) $r['id'],
            'display_name' => SquadRules::displayName((string) $r['name'], (string) $r['specialization']),
            'title' => ((int) $r['show_squad_title'] === 1 && $r['role'] !== 'member') ? (string) $r['role'] : null,
        ];
    }
}
