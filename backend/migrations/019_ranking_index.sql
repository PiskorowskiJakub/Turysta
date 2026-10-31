-- Ranking graczy sortuje po punktach.
ALTER TABLE wallets ADD KEY idx_wallets_points (points);
