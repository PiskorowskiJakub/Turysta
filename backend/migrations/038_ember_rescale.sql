-- Zar za dzien spada z bazy 100 do 10 (zeby sumy nie rosly do ogromnych wartosci), wiec przeliczamy dotychczasowy zar w tej samej skali.
UPDATE squads SET ember = ROUND(ember / 10);
UPDATE squad_fire_days SET ember = ROUND(ember / 10);
