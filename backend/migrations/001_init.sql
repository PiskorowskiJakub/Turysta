-- Etap 0: tylko potwierdzenie, ze migracje dzialaja. Model danych w etapie 1.
CREATE TABLE app_meta (
    meta_key VARCHAR(64) NOT NULL PRIMARY KEY,
    meta_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO app_meta (meta_key, meta_value) VALUES ('schema_version', '0');
