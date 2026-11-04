-- Ciekawostka dnia zapisana na stale: raz wybrana na dany dzien gry nie zmienia sie, nawet gdy lista ciekawostek zostanie zmieniona.
CREATE TABLE daily_fact_picks (
    day DATE NOT NULL PRIMARY KEY,
    fact_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_fact_picks_fact FOREIGN KEY (fact_id) REFERENCES daily_facts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
