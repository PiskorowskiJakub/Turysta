-- Izba pamieci (Etap 5): emblemat i ramka oddzialu oraz gablota wyroznionych odznak.
ALTER TABLE squads
    ADD COLUMN emblem VARCHAR(20) NULL,
    ADD COLUMN frame VARCHAR(10) NULL,
    -- Kody wyroznionych odznak oddzielone przecinkami, w kolejnosci wyswietlania.
    ADD COLUMN showcase VARCHAR(80) NULL;
