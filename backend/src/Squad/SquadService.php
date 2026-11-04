<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;
use Turysta\Auth\RateLimiter;
use Turysta\Game\Clock;
use Turysta\Game\Notifier;
use Turysta\Http\ApiException;

/**
 * Oddzialy: zakladanie, czlonkostwo (otwarty, na zgloszenie, na zaproszenie), role i ustawienia.
 * Uprawnienia: Przywodca i Zastepca moga wszystko poza przekazaniem roli Przywodcy i rozwiazaniem oddzialu;
 * Rekruter zaprasza i przyjmuje zgloszenia; pozostale role w Etapie 1 sa tytulami.
 */
final class SquadService
{
    public function __construct(private PDO $pdo, private RateLimiter $limiter)
    {
    }

    // ---------------------------------------------------------------- warunki

    /** Numer dnia gry konta, wlacznie z dniem zalozenia (pierwszy dzien = 1). @param array<string,mixed> $user */
    public function accountDays(array $user): int
    {
        $today = new DateTimeImmutable(Clock::gameDay(Clock::now($this->pdo)));
        $created = new DateTimeImmutable(Clock::gameDay(Clock::parse((string) $user['created_at'])));
        return 1 + (int) $created->diff($today)->days;
    }

    /**
     * Czy gracz moze zalozyc oddzial albo do niego dolaczyc; przy odmowie kod powodu.
     *
     * @param array<string,mixed> $user wiersz users
     * @return array{ok:bool,reason:?string,until:?string}
     */
    public function eligibility(array $user): array
    {
        $uid = (int) $user['id'];
        if ($this->memberRow($uid) !== null) {
            return ['ok' => false, 'reason' => 'already_member', 'until' => null];
        }
        if ($user['email_verified_at'] === null) {
            return ['ok' => false, 'reason' => 'email_unverified', 'until' => null];
        }
        if ($this->accountDays($user) < SquadRules::MIN_ACCOUNT_DAYS) {
            return ['ok' => false, 'reason' => 'account_too_young', 'until' => null];
        }
        if (!empty($user['squad_left_at'])) {
            $until = Clock::parse((string) $user['squad_left_at'])->modify('+' . SquadRules::REJOIN_COOLDOWN_DAYS . ' days');
            if ($until > Clock::now($this->pdo)) {
                return ['ok' => false, 'reason' => 'squad_cooldown', 'until' => Clock::iso(Clock::fmt($until))];
            }
        }
        return ['ok' => true, 'reason' => null, 'until' => null];
    }

    /** @param array<string,mixed> $user */
    private function assertEligible(array $user, string $who = 'self'): void
    {
        $e = $this->eligibility($user);
        if ($e['ok']) {
            return;
        }
        $self = [
            'already_member' => [409, 'Jesteś już w oddziale.'],
            'email_unverified' => [403, 'Potwierdź adres e-mail, aby korzystać z oddziałów.'],
            'account_too_young' => [403, 'Oddziały są dostępne od ' . SquadRules::MIN_ACCOUNT_DAYS . '. dnia gry.'],
            'squad_cooldown' => [403, 'Po wyjściu z oddziału musisz poczekać ' . SquadRules::REJOIN_COOLDOWN_DAYS . ' dni.'],
        ];
        $other = [
            'already_member' => [409, 'Ten gracz jest już w oddziale.'],
            'email_unverified' => [409, 'Ten gracz nie potwierdził adresu e-mail.'],
            'account_too_young' => [409, 'Ten gracz gra krócej niż ' . SquadRules::MIN_ACCOUNT_DAYS . ' dni.'],
            'squad_cooldown' => [409, 'Ten gracz niedawno opuścił oddział i musi chwilę poczekać.'],
        ];
        [$status, $msg] = ($who === 'self' ? $self : $other)[(string) $e['reason']];
        throw new ApiException($status, (string) $e['reason'], $msg);
    }

    // ---------------------------------------------------------------- zakladanie

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $in name, specialization, join_mode
     * @return array{id:int}
     */
    public function create(array $user, array $in, string $ip): array
    {
        $uid = (int) $user['id'];
        if ($this->limiter->hit('squad_create:' . $uid, 5, 3600) || $this->limiter->hit('squad_create:ip:' . $ip, 20, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób założenia oddziału. Spróbuj za godzinę.');
        }
        $this->assertEligible($user);

        $name = $this->cleanName($in['name'] ?? null);
        $spec = $this->spec($in['specialization'] ?? null);
        $mode = $this->mode($in['join_mode'] ?? 'open');
        $this->assertNameAllowed($name, null);

        $this->pdo->beginTransaction();
        try {
            $now = Clock::now($this->pdo);
            $this->pdo->prepare(
                'INSERT INTO squads (name, name_key, specialization, join_mode, created_by, last_fire_day) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$name, NameFilter::uniqueKey($name), $spec, $mode, $uid, Clock::gameDay($now)]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("INSERT INTO squad_members (user_id, squad_id, role) VALUES (?, ?, 'leader')")->execute([$uid, $id]);
            $this->pdo->prepare('INSERT INTO squad_name_history (squad_id, name, changed_by) VALUES (?, ?, ?)')->execute([$id, $name, $uid]);
            $this->pdo->prepare('DELETE FROM squad_requests WHERE user_id = ?')->execute([$uid]);
            SquadChronicle::record($this->pdo, $id, 'created', 'created', ['user' => $uid]);
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Oddział o takiej nazwie już istnieje.']);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return ['id' => $id];
    }

    // ---------------------------------------------------------------- dolaczanie

    /** Dolaczenie do oddzialu otwartego albo zlozenie zgloszenia (tryb na zgloszenie). @param array<string,mixed> $user */
    public function join(array $user, int $squadId): string
    {
        $uid = (int) $user['id'];
        $this->assertEligible($user);
        $squad = $this->squad($squadId);

        $stmt = $this->pdo->prepare('SELECT kind FROM squad_requests WHERE squad_id = ? AND user_id = ?');
        $stmt->execute([$squadId, $uid]);
        $existing = $stmt->fetchColumn();
        if ($existing === 'invite') {
            $this->addMember($squadId, $uid);
            return 'joined';
        }
        if ($squad['join_mode'] === 'invite') {
            throw new ApiException(403, 'invite_only', 'Do tego oddziału dołącza się tylko na zaproszenie.');
        }
        if ($squad['join_mode'] === 'open') {
            $this->addMember($squadId, $uid);
            return 'joined';
        }

        if ($existing === 'request') {
            throw new ApiException(409, 'request_pending', 'Zgłoszenie do tego oddziału już czeka na odpowiedź.');
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM squad_requests WHERE user_id = ? AND kind = 'request'");
        $stmt->execute([$uid]);
        if ((int) $stmt->fetchColumn() >= SquadRules::MAX_PENDING_REQUESTS_PER_USER) {
            throw new ApiException(409, 'request_limit', 'Masz już ' . SquadRules::MAX_PENDING_REQUESTS_PER_USER . ' oczekujących zgłoszeń. Wycofaj któreś z nich.');
        }
        $this->pdo->prepare("INSERT INTO squad_requests (squad_id, user_id, kind, created_by) VALUES (?, ?, 'request', ?)")->execute([$squadId, $uid, $uid]);
        $this->notifyRecruiters($squadId, 'squad_request', ['name' => (string) $user['character_name'], 'squad' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization'])]);
        return 'requested';
    }

    /** Wycofanie wlasnego zgloszenia albo odrzucenie zaproszenia. */
    public function dropOwnRequest(int $userId, int $squadId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM squad_requests WHERE squad_id = ? AND user_id = ?');
        $stmt->execute([$squadId, $userId]);
        if ($stmt->rowCount() === 0) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zgłoszenia ani zaproszenia.');
        }
    }

    /** Przyjecie zaproszenia przez gracza. @param array<string,mixed> $user */
    public function acceptInvite(array $user, int $squadId): void
    {
        $uid = (int) $user['id'];
        $stmt = $this->pdo->prepare("SELECT 1 FROM squad_requests WHERE squad_id = ? AND user_id = ? AND kind = 'invite'");
        $stmt->execute([$squadId, $uid]);
        if (!$stmt->fetchColumn()) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zaproszenia.');
        }
        $this->assertEligible($user);
        $this->squad($squadId);
        $this->addMember($squadId, $uid);
    }

    /**
     * Zaproszenie gracza (po nazwie postaci) przez Przywodce, Zastepce albo Rekrutera. Nie wymaga relacji znajomych.
     *
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in name
     * @return array{name:string,joined:bool}
     */
    public function invite(array $actor, int $squadId, array $in): array
    {
        $this->requirePower($actor, $squadId, 'recruit');
        if ($this->limiter->hit('squad_invite:' . $actor['id'], 30, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo zaproszeń. Spróbuj za godzinę.');
        }
        $name = is_string($in['name'] ?? null) ? trim($in['name']) : '';
        if ($name === '') {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Podaj nazwę postaci gracza.']);
        }
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE character_name = ? AND status = 'active'");
        $stmt->execute([$name]);
        $target = $stmt->fetch();
        if (!$target) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Nie ma gracza o takiej nazwie.']);
        }
        $this->assertEligible($target, 'other');

        $squad = $this->squad($squadId);
        $stmt = $this->pdo->prepare('SELECT kind FROM squad_requests WHERE squad_id = ? AND user_id = ?');
        $stmt->execute([$squadId, (int) $target['id']]);
        $existing = $stmt->fetchColumn();
        if ($existing === 'request') {
            $this->addMember($squadId, (int) $target['id']);
            return ['name' => (string) $target['character_name'], 'joined' => true];
        }
        if ($existing === 'invite') {
            throw new ApiException(409, 'invite_pending', 'Ten gracz już dostał zaproszenie.');
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM squad_requests WHERE squad_id = ? AND kind = 'invite'");
        $stmt->execute([$squadId]);
        if ((int) $stmt->fetchColumn() >= SquadRules::MAX_PENDING_INVITES_PER_SQUAD) {
            throw new ApiException(409, 'invite_limit', 'Oddział ma już ' . SquadRules::MAX_PENDING_INVITES_PER_SQUAD . ' oczekujących zaproszeń.');
        }
        $this->pdo->prepare("INSERT INTO squad_requests (squad_id, user_id, kind, created_by) VALUES (?, ?, 'invite', ?)")
            ->execute([$squadId, (int) $target['id'], (int) $actor['id']]);
        Notifier::add($this->pdo, (int) $target['id'], 'squad_invite', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization'])]);
        return ['name' => (string) $target['character_name'], 'joined' => false];
    }

    /** Przyjecie zgloszenia gracza przez uprawniona osobe. @param array<string,mixed> $actor */
    public function approveRequest(array $actor, int $squadId, int $userId): void
    {
        $this->requirePower($actor, $squadId, 'recruit');
        $stmt = $this->pdo->prepare("SELECT 1 FROM squad_requests WHERE squad_id = ? AND user_id = ? AND kind = 'request'");
        $stmt->execute([$squadId, $userId]);
        if (!$stmt->fetchColumn()) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zgłoszenia.');
        }
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zgłoszenia.');
        }
        $this->assertEligible($target, 'other');
        $this->addMember($squadId, $userId);
        $squad = $this->squad($squadId);
        Notifier::add($this->pdo, $userId, 'squad_request_accepted', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization'])]);
    }

    /** Odrzucenie zgloszenia albo cofniecie zaproszenia przez uprawniona osobe. @param array<string,mixed> $actor */
    public function dismissRequest(array $actor, int $squadId, int $userId): void
    {
        $this->requirePower($actor, $squadId, 'recruit');
        $stmt = $this->pdo->prepare('SELECT kind FROM squad_requests WHERE squad_id = ? AND user_id = ?');
        $stmt->execute([$squadId, $userId]);
        $kind = $stmt->fetchColumn();
        if (!$kind) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zgłoszenia.');
        }
        $this->pdo->prepare('DELETE FROM squad_requests WHERE squad_id = ? AND user_id = ?')->execute([$squadId, $userId]);
        if ($kind === 'request') {
            $squad = $this->squad($squadId);
            Notifier::add($this->pdo, $userId, 'squad_request_declined', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization'])]);
        }
    }

    // ---------------------------------------------------------------- wyjscie, wyrzucenie, rozwiazanie

    /** @param array<string,mixed> $user */
    public function leave(array $user): void
    {
        $uid = (int) $user['id'];
        $m = $this->memberRow($uid);
        if (!$m) {
            throw new ApiException(404, 'not_member', 'Nie jesteś w żadnym oddziale.');
        }
        $squadId = (int) $m['squad_id'];
        if ($m['role'] === 'leader') {
            if ($this->countMembers($squadId) > 1) {
                throw new ApiException(409, 'leader_cannot_leave', 'Przywódca musi najpierw przekazać rolę innej osobie.');
            }
            $this->disband($user, $squadId);
            return;
        }
        $this->removeMember($squadId, $uid);
        SquadChronicle::record($this->pdo, $squadId, 'left', 'left:' . $uid . ':' . time(), ['user' => $uid]);
    }

    /**
     * Wyrzucenie czlonka z powodem z listy (zapis w kronice, powiadomienie dla gracza).
     *
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in reason
     */
    public function kick(array $actor, int $squadId, int $userId, array $in): void
    {
        $role = $this->requirePower($actor, $squadId, 'manage');
        $reason = is_string($in['reason'] ?? null) ? $in['reason'] : '';
        if (!in_array($reason, SquadRules::KICK_REASONS, true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['reason' => 'Wybierz powód z listy.']);
        }
        $target = $this->memberRow($userId);
        if (!$target || (int) $target['squad_id'] !== $squadId) {
            throw new ApiException(404, 'not_found', 'Tego gracza nie ma w oddziale.');
        }
        if ($userId === (int) $actor['id']) {
            throw new ApiException(422, 'validation_failed', 'Nie możesz wyrzucić samego siebie.');
        }
        if ($target['role'] === 'leader' || ($target['role'] === 'deputy' && $role !== 'leader')) {
            throw new ApiException(403, 'forbidden', 'Nie możesz wyrzucić tej osoby.');
        }
        $squad = $this->squad($squadId);
        $this->removeMember($squadId, $userId);
        SquadChronicle::record($this->pdo, $squadId, 'kicked', 'kicked:' . $userId . ':' . time(), ['target' => $userId, 'reason' => $reason]);
        Notifier::add($this->pdo, $userId, 'squad_kicked', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization'])]);
    }

    /** Rozwiazanie oddzialu: tylko Przywodca. @param array<string,mixed> $actor */
    public function disband(array $actor, int $squadId): void
    {
        $role = $this->requirePower($actor, $squadId, 'lead');
        if ($role !== 'leader') {
            throw new ApiException(403, 'forbidden', 'Tylko Przywódca może rozwiązać oddział.');
        }
        $this->archive($squadId, true);
    }

    /** Archiwizacja: czlonkowie wychodza (z karencja albo bez), nazwa wraca do puli, wiersz zostaje jako historia. */
    public function archive(int $squadId, bool $cooldown): void
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM squad_members WHERE squad_id = ?');
        $stmt->execute([$squadId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $this->pdo->beginTransaction();
        try {
            if ($cooldown && $ids) {
                $this->pdo->exec('UPDATE users SET squad_left_at = UTC_TIMESTAMP() WHERE id IN (' . implode(',', $ids) . ')');
            }
            $this->pdo->prepare('DELETE FROM squad_members WHERE squad_id = ?')->execute([$squadId]);
            $this->pdo->prepare('DELETE FROM squad_requests WHERE squad_id = ?')->execute([$squadId]);
            $this->pdo->prepare('UPDATE squads SET archived_at = UTC_TIMESTAMP(), name_key = NULL WHERE id = ?')->execute([$squadId]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ---------------------------------------------------------------- role i ustawienia

    /**
     * Zmiana roli czlonka (Przywodca i Zastepca). Przywodce zmienia sie tylko przekazaniem roli.
     *
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in role
     */
    public function setRole(array $actor, int $squadId, int $userId, array $in): void
    {
        $this->requirePower($actor, $squadId, 'manage');
        $role = is_string($in['role'] ?? null) ? $in['role'] : '';
        if (!in_array($role, SquadRules::ASSIGNABLE_ROLES, true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['role' => 'Wybierz rolę z listy.']);
        }
        $target = $this->memberRow($userId);
        if (!$target || (int) $target['squad_id'] !== $squadId) {
            throw new ApiException(404, 'not_found', 'Tego gracza nie ma w oddziale.');
        }
        if ($target['role'] === 'leader') {
            throw new ApiException(403, 'forbidden', 'Rolę Przywódcy zmienia się przez jej przekazanie.');
        }
        if ($target['role'] === $role) {
            return;
        }
        $limit = SquadRules::ROLE_LIMITS[$role];
        if ($limit !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM squad_members WHERE squad_id = ? AND role = ? AND user_id <> ?');
            $stmt->execute([$squadId, $role, $userId]);
            if ((int) $stmt->fetchColumn() >= $limit) {
                throw new ApiException(409, 'role_full', 'Ta rola ma już komplet osób (' . $limit . ').');
            }
        }
        $this->pdo->prepare('UPDATE squad_members SET role = ? WHERE user_id = ?')->execute([$role, $userId]);
        SquadChronicle::record($this->pdo, $squadId, 'role', 'role:' . $userId . ':' . time(), ['target' => $userId, 'role' => $role]);
        $squad = $this->squad($squadId);
        Notifier::add($this->pdo, $userId, 'squad_role', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization']), 'role' => $role]);
    }

    /**
     * Przekazanie roli Przywodcy. Dotychczasowy Przywodca zostaje Zastepca (zamiana, gdy cel byl Zastepca).
     *
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in user_id
     */
    public function transfer(array $actor, int $squadId, array $in): void
    {
        $role = $this->requirePower($actor, $squadId, 'lead');
        if ($role !== 'leader') {
            throw new ApiException(403, 'forbidden', 'Tylko Przywódca może przekazać swoją rolę.');
        }
        $targetId = (int) ($in['user_id'] ?? 0);
        $target = $this->memberRow($targetId);
        if ($targetId === (int) $actor['id'] || !$target || (int) $target['squad_id'] !== $squadId) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['user_id' => 'Wybierz innego członka oddziału.']);
        }
        $this->promoteLeader($squadId, $targetId, (int) $actor['id'], 'transfer');
    }

    /**
     * Zmiana nazwy, specjalizacji i trybu przyjmowania (Przywodca i Zastepca).
     *
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in
     */
    public function update(array $actor, int $squadId, array $in): void
    {
        $this->requirePower($actor, $squadId, 'manage');
        $squad = $this->squad($squadId);
        $now = Clock::now($this->pdo);
        $sets = [];
        $params = [];
        $newName = null;
        $newSpec = null;

        if (array_key_exists('name', $in) && $in['name'] !== $squad['name']) {
            if ((int) $squad['name_locked'] === 1) {
                throw new ApiException(403, 'name_locked', 'Zmiana nazwy tego oddziału jest zablokowana przez administratora.');
            }
            $newName = $this->cleanName($in['name']);
            if ((int) $squad['name_forced'] !== 1 && $squad['name_changed_at'] !== null) {
                $next = Clock::parse((string) $squad['name_changed_at'])->modify('+' . SquadRules::NAME_CHANGE_DAYS . ' days');
                if ($next > $now) {
                    throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Nazwę można zmienić raz na ' . SquadRules::NAME_CHANGE_DAYS . ' dni.']);
                }
            }
            $this->assertNameAllowed($newName, $squadId);
            $sets[] = 'name = ?, name_key = ?, name_changed_at = UTC_TIMESTAMP(), name_forced = 0';
            array_push($params, $newName, NameFilter::uniqueKey($newName));
        }
        if (array_key_exists('specialization', $in) && $in['specialization'] !== $squad['specialization']) {
            $newSpec = $this->spec($in['specialization']);
            if ($squad['spec_changed_at'] !== null) {
                $next = Clock::parse((string) $squad['spec_changed_at'])->modify('+' . SquadRules::SPEC_CHANGE_DAYS . ' days');
                if ($next > $now) {
                    throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['specialization' => 'Specjalizację można zmienić raz na ' . SquadRules::SPEC_CHANGE_DAYS . ' dni.']);
                }
            }
            $sets[] = 'specialization = ?, spec_changed_at = UTC_TIMESTAMP()';
            $params[] = $newSpec;
        }
        if (array_key_exists('join_mode', $in) && $in['join_mode'] !== $squad['join_mode']) {
            $sets[] = 'join_mode = ?';
            $params[] = $this->mode($in['join_mode']);
        }
        if (!$sets) {
            return;
        }
        $params[] = $squadId;
        try {
            $this->pdo->prepare('UPDATE squads SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Oddział o takiej nazwie już istnieje.']);
            }
            throw $e;
        }
        if ($newName !== null) {
            $this->pdo->prepare('INSERT INTO squad_name_history (squad_id, name, changed_by) VALUES (?, ?, ?)')->execute([$squadId, $newName, (int) $actor['id']]);
            SquadChronicle::record($this->pdo, $squadId, 'name', 'name:' . time(), ['user' => (int) $actor['id'], 'name' => $newName]);
        }
        if ($newSpec !== null) {
            SquadChronicle::record($this->pdo, $squadId, 'spec', 'spec:' . time(), ['user' => (int) $actor['id'], 'spec' => $newSpec]);
        }
    }

    /**
     * Zgloszenie nazwy oddzialu przez gracza (jedno zgloszenie na gracza i oddzial).
     *
     * @param array<string,mixed> $user
     * @param array<string,mixed> $in reason
     */
    public function reportName(array $user, int $squadId, array $in, string $ip): void
    {
        if ($this->limiter->hit('squad_report:' . $user['id'], 10, 3600) || $this->limiter->hit('squad_report:ip:' . $ip, 30, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo zgłoszeń. Spróbuj za godzinę.');
        }
        $reason = is_string($in['reason'] ?? null) ? $in['reason'] : '';
        if (!in_array($reason, SquadRules::REPORT_REASONS, true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['reason' => 'Wybierz powód z listy.']);
        }
        $squad = $this->squad($squadId);
        try {
            $this->pdo->prepare('INSERT INTO squad_name_reports (squad_id, reporter_id, reason, reported_name) VALUES (?, ?, ?, ?)')
                ->execute([$squadId, (int) $user['id'], $reason, (string) $squad['name']]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'already_reported', 'Już zgłosiłeś nazwę tego oddziału.');
            }
            throw $e;
        }
    }

    // ---------------------------------------------------------------- wspolne

    /** Wiersz squad_members gracza albo null. @return array<string,mixed>|null */
    public function memberRow(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squad_members WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string,mixed> */
    public function squad(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squads WHERE id = ? AND archived_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'squad_not_found', 'Nie znaleziono oddziału.');
        }
        return $row;
    }

    public function countMembers(int $squadId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM squad_members WHERE squad_id = ?');
        $stmt->execute([$squadId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Sprawdza uprawnienie osoby wykonujacej akcje i zwraca jej role w oddziale.
     * 'manage' i 'lead' = Przywodca i Zastepca (wywolujacy sam sprawdza, czy to musi byc Przywodca); 'recruit' = tez Rekruter; 'plan' = tez Przewodnik (planowanie wypraw).
     *
     * @param array<string,mixed> $actor
     */
    public function requirePower(array $actor, int $squadId, string $power): string
    {
        $m = $this->memberRow((int) $actor['id']);
        if (!$m || (int) $m['squad_id'] !== $squadId) {
            throw new ApiException(403, 'forbidden', 'Nie masz uprawnień w tym oddziale.');
        }
        $role = (string) $m['role'];
        $ok = match ($power) {
            'recruit' => in_array($role, ['leader', 'deputy', 'recruiter'], true),
            'manage', 'lead' => in_array($role, ['leader', 'deputy'], true),
            'plan' => in_array($role, ['leader', 'deputy', 'guide'], true),
            default => false,
        };
        if (!$ok) {
            throw new ApiException(403, 'forbidden', 'Nie masz uprawnień do tej czynności.');
        }
        $this->squad($squadId);
        return $role;
    }

    /** Dodaje gracza do oddzialu z kontrola limitu (blokada wiersza oddzialu) i sprzata jego zgloszenia. */
    private function addMember(int $squadId, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM squads WHERE id = ? FOR UPDATE')->execute([$squadId]);
            if ($this->countMembers($squadId) >= SquadRules::MAX_MEMBERS) {
                throw new ApiException(409, 'squad_full', 'Oddział ma już komplet ' . SquadRules::MAX_MEMBERS . ' osób.');
            }
            $this->pdo->prepare("INSERT INTO squad_members (user_id, squad_id, role) VALUES (?, ?, 'member')")->execute([$userId, $squadId]);
            $this->pdo->prepare('DELETE FROM squad_requests WHERE user_id = ?')->execute([$userId]);
            SquadChronicle::record($this->pdo, $squadId, 'joined', 'joined:' . $userId . ':' . time(), ['user' => $userId]);
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'already_member', 'Ten gracz jest już w oddziale.');
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Usuwa czlonka i zapisuje czas wyjscia (karencja). */
    private function removeMember(int $squadId, int $userId): void
    {
        $this->pdo->prepare('DELETE FROM squad_members WHERE user_id = ? AND squad_id = ?')->execute([$userId, $squadId]);
        $this->pdo->prepare('UPDATE users SET squad_left_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$userId]);
    }

    /** Nowy Przywodca. Poprzedni (jesli jest) zostaje Zastepca, gdy to miejsce wolne albo zamieniane z celem; inaczej zwyklym czlonkiem. */
    public function promoteLeader(int $squadId, int $newLeaderId, ?int $oldLeaderId, string $why): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT role FROM squad_members WHERE user_id = ?');
            $stmt->execute([$newLeaderId]);
            $newWas = (string) $stmt->fetchColumn();
            if ($oldLeaderId !== null) {
                $oldNew = 'deputy';
                if ($newWas !== 'deputy') {
                    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM squad_members WHERE squad_id = ? AND role = 'deputy'");
                    $stmt->execute([$squadId]);
                    $oldNew = (int) $stmt->fetchColumn() === 0 ? 'deputy' : 'member';
                }
                $this->pdo->prepare('UPDATE squad_members SET role = ? WHERE user_id = ?')->execute([$oldNew, $oldLeaderId]);
            }
            $this->pdo->prepare("UPDATE squad_members SET role = 'leader' WHERE user_id = ?")->execute([$newLeaderId]);
            SquadChronicle::record($this->pdo, $squadId, 'leader', 'leader:' . $newLeaderId . ':' . time(), ['target' => $newLeaderId, 'why' => $why]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $squad = $this->squad($squadId);
        Notifier::add($this->pdo, $newLeaderId, 'squad_role', ['name' => SquadRules::displayName((string) $squad['name'], (string) $squad['specialization']), 'role' => 'leader']);
    }

    /** Powiadomienie Przywodcy, Zastepcy i Rekruterow oddzialu. @param array<string,mixed> $payload */
    private function notifyRecruiters(int $squadId, string $type, array $payload): void
    {
        $stmt = $this->pdo->prepare("SELECT user_id FROM squad_members WHERE squad_id = ? AND role IN ('leader','deputy','recruiter')");
        $stmt->execute([$squadId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            Notifier::add($this->pdo, (int) $id, $type, $payload);
        }
    }

    private function cleanName(mixed $raw): string
    {
        $name = is_string($raw) ? trim((string) preg_replace('/\s+/u', ' ', $raw)) : '';
        $err = NameFilter::formatError($name);
        if ($err !== null) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => $err]);
        }
        return $name;
    }

    public function assertNameAllowed(string $name, ?int $exceptSquad): void
    {
        if (NameFilter::isBanned($this->pdo, $name)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Ta nazwa zawiera niedozwolone słowo.']);
        }
        $stmt = $this->pdo->prepare('SELECT id FROM squads WHERE name_key = ? AND id <> ?');
        $stmt->execute([NameFilter::uniqueKey($name), $exceptSquad ?? 0]);
        if ($stmt->fetchColumn()) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Oddział o takiej nazwie już istnieje.']);
        }
    }

    private function spec(mixed $raw): string
    {
        if (!is_string($raw) || !isset(SquadRules::SPECIALIZATIONS[$raw])) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['specialization' => 'Wybierz specjalizację z listy.']);
        }
        return $raw;
    }

    private function mode(mixed $raw): string
    {
        if (!is_string($raw) || !in_array($raw, ['open', 'request', 'invite'], true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['join_mode' => 'Wybierz tryb przyjmowania.']);
        }
        return $raw;
    }
}
