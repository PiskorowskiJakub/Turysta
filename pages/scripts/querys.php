<?php

    // -------------------------------------------------------------------------------------------------------------------------------------------
    // INSERT INTO sql queries
    // -------------------------------------------------------------------------------------------------------------------------------------------
    $sqlCreateNewUser = "INSERT INTO users (ID, Nazwa, Email, Haslo, DataStworzenia) VALUES (NULL ,? ,? ,? ,? )";

    $sqlInsertInvCode = "INSERT INTO kodypolecenia (ID, IDUzytkownika, KodPolecajacy, KodPolecajacego) VALUES (NULL,?, ?, ?)";
    $sqlAccoountStatus = "INSERT INTO statuskonta (ID, IDUzytkownika, StatusKonta, Grupa) VALUES (NULL, ?, ?, ?)";
    $sqlInsertWallet = "INSERT INTO portfel (ID, IDUzytkownika, Monety, Bilety, Punkty, Swiat, Rozdzial) VALUES (NULL , ?, ?, ?, ?, ?, ?)";

    $sqlInsertWorkMarketLog ="INSERT INTO logdzialalnosci (ID, IDUzytkownika, IDTypuZarobku, DataZarobku, Zarobek, DodanyProfit) VALUES (NULL, ?, ?, ?, ?, ?)";
    $sqlInsertSkillsData1 = "INSERT INTO logumiejetnosci(ID, IDUzytkownika, IDUmiejetnosci, Data, Koszt, Poziom) VALUES (NULL, ?,'1', ?,'3','0')";
    $sqlInsertSkillsData2 = "INSERT INTO logumiejetnosci(ID, IDUzytkownika, IDUmiejetnosci, Data, Koszt, Poziom) VALUES (NULL, ?,'2', ?,'3','0')";
    $sqlInsertSkillsData3 = "INSERT INTO logumiejetnosci(ID, IDUzytkownika, IDUmiejetnosci, Data, Koszt, Poziom) VALUES (NULL, ?,'3', ?,'7','0')";

    $sqlInsertUserLogin = "INSERT INTO logowania (ID, IDUzytkownika, DataZalogowania, DataWylogowania) VALUES (NULL , ?, ?, ?)"; 

    // -------------------------------------------------------------------------------------------------------------------------------------------
    // SELECT sql queries
    // -------------------------------------------------------------------------------------------------------------------------------------------
    $sqlCheckUserExist = "SELECT COUNT(*) FROM users WHERE Nazwa=? OR Email=?";
    $sqlCodeExist = "SELECT COUNT(*) FROM kodypolecenia WHERE KodPolecajacy = ?";
    $sqlFindUser = "SELECT Email, Haslo FROM users WHERE Email=? ";
    $sqlGetUserId = "SELECT ID FROM users WHERE Email= ? ";

    $sqlGetUserProfileData = "SELECT users.Nazwa, users.Email, users.DataStworzenia, statusnazwa.Nazwa, grupanazwa.Nazwa, portfel.Monety, portfel.Bilety, portfel.Punkty, portfel.Swiat, portfel.Rozdzial FROM statuskonta INNER JOIN statusnazwa ON statusnazwa.ID = statuskonta.StatusKonta INNER JOIN grupanazwa ON grupanazwa.ID = statuskonta.Grupa INNER JOIN users ON users.ID = statuskonta.IDUzytkownika INNER JOIN portfel ON users.ID = portfel.IDUzytkownika WHERE users.ID = ?";
    $sqlGetWorkMarketInfo = "SELECT typdzialalnosci.Nazwa, typdzialalnosci.CzasTrwania, typdzialalnosci.WspolczynnikZarobku, logdzialalnosci.DataZarobku, logdzialalnosci.Zarobek, logdzialalnosci.DodanyProfit FROM typdzialalnosci INNER JOIN logdzialalnosci ON logdzialalnosci.IDTypuZarobku = typdzialalnosci.ID WHERE typdzialalnosci.ID = ? AND logdzialalnosci.IDUzytkownika = ?;";

    $sqlGetLvlSkill = "SELECT Poziom FROM logumiejetnosci WHERE IDUmiejetnosci=? AND IDUzytkownika=?";

    // -------------------------------------------------------------------------------------------------------------------------------------------
    // Update sql queries
    // -------------------------------------------------------------------------------------------------------------------------------------------
    $sqlLogoutUser = "UPDATE logowania SET DataWylogowania=? WHERE IDUzytkownika= ?";
    $sqlUpdateWorkMarketLog = "UPDATE logdzialalnosci SET DataZarobku=?, Zarobek=?, DodanyProfit=? WHERE IDUzytkownika=? AND IDTypuZarobku=?";
    $sqlUpdateCoinInWallet = "UPDATE portfel SET Monety=? WHERE IDUzytkownika = ?";

?>