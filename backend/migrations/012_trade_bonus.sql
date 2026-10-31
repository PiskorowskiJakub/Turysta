-- Bonus Handlu: wyplata za prace jest zapisywana w chwili startu (z uwzglednieniem poziomu Handlu).
ALTER TABLE job_runs ADD COLUMN reward_snapshot_cents BIGINT NULL AFTER payout_cents;
