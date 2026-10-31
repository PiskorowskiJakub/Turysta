-- Swiaty numerowane od 1 (wczesniej od 0).
UPDATE worlds SET ordinal = ordinal + 1 ORDER BY ordinal DESC;
