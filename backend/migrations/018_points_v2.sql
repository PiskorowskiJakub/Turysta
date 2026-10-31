-- Punkty (ranking i prestiz): nowe zasady, rok gry to rzad wielkosci kilkudziesieciu tysiecy.
--   szkolenie umiejetnosci: 1 punkt za kazde rozpoczete 20 minut (minimum 1)
--   awans: 50 x numer swiata, wejscie do nowego swiata 1000
--   praca: 1 punkt za kazde pelne 50 dk
-- Istniejacym graczom saldo punktow przeliczamy z historii (ukonczone szkolenia, awanse i prace),
-- a roznice zapisujemy w ksiedze jako 'points_rescale'.

INSERT INTO ledger_entries (user_id, currency, amount, balance_after, reason)
SELECT t.user_id, 'points', t.total - t.old_points, t.total, 'points_rescale'
FROM (
    SELECT w.user_id, w.points AS old_points,
        (COALESCE((SELECT SUM(GREATEST(1, CEIL(q.duration_seconds / 1200))) FROM training_queue q
                  WHERE q.user_id = w.user_id AND q.status = 'done' AND q.skill_id IS NOT NULL), 0)
         + COALESCE((SELECT SUM(IF(c.ordinal = 1, 1000, 50 * wo.ordinal)) FROM training_queue q
                  JOIN chapters c ON c.id = q.chapter_id JOIN worlds wo ON wo.id = c.world_id
                  WHERE q.user_id = w.user_id AND q.status = 'done'), 0)
         + COALESCE((SELECT SUM(FLOOR(j.payout_cents / 5000)) FROM job_runs j
                  WHERE j.user_id = w.user_id AND j.status IN ('done', 'cancelled')), 0)) AS total
    FROM wallets w
) t
WHERE t.total <> t.old_points;

UPDATE wallets w
SET w.points = (COALESCE((SELECT SUM(GREATEST(1, CEIL(q.duration_seconds / 1200))) FROM training_queue q
                  WHERE q.user_id = w.user_id AND q.status = 'done' AND q.skill_id IS NOT NULL), 0)
         + COALESCE((SELECT SUM(IF(c.ordinal = 1, 1000, 50 * wo.ordinal)) FROM training_queue q
                  JOIN chapters c ON c.id = q.chapter_id JOIN worlds wo ON wo.id = c.world_id
                  WHERE q.user_id = w.user_id AND q.status = 'done'), 0)
         + COALESCE((SELECT SUM(FLOOR(j.payout_cents / 5000)) FROM job_runs j
                  WHERE j.user_id = w.user_id AND j.status IN ('done', 'cancelled')), 0));
