-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Czas generowania: 09 Kwi 2023, 21:52
-- Wersja serwera: 10.4.25-MariaDB
-- Wersja PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `turysta-game`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `grupanazwa`
--

CREATE TABLE `grupanazwa` (
  `ID` int(11) NOT NULL,
  `Nazwa` varchar(255) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `grupanazwa`
--

INSERT INTO `grupanazwa` (`ID`, `Nazwa`) VALUES
(1, 'Administrator'),
(2, 'Moderator'),
(3, 'Uzytkownik'),
(4, 'Gosc'),
(5, 'VIP'),
(6, 'Partner');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kodypolecenia`
--

CREATE TABLE `kodypolecenia` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `KodPolecajacy` varchar(50) COLLATE utf8_polish_ci NOT NULL,
  `KodPolecajacego` varchar(50) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `kodypolecenia`
--

INSERT INTO `kodypolecenia` (`ID`, `IDUzytkownika`, `KodPolecajacy`, `KodPolecajacego`) VALUES
(11, 23, '4NF62Y', '000000'),
(15, 32, '0FB3CA', '000000'),
(16, 33, 'QESEND', '000000'),
(17, 34, 'ERGVUI', '000000');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logdzialalnosci`
--

CREATE TABLE `logdzialalnosci` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `IDTypuZarobku` int(11) NOT NULL,
  `DataZarobku` datetime NOT NULL,
  `Zarobek` decimal(65,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `logdzialalnosci`
--

INSERT INTO `logdzialalnosci` (`ID`, `IDUzytkownika`, `IDTypuZarobku`, `DataZarobku`, `Zarobek`) VALUES
(4, 23, 1, '2023-04-10 05:22:15', '180.00'),
(7, 32, 1, '2023-03-23 13:51:04', '120.00'),
(8, 33, 1, '2023-03-25 20:15:42', '120.00'),
(9, 34, 1, '2023-03-30 17:56:03', '120.00');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logowania`
--

CREATE TABLE `logowania` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `DataZalogowania` datetime NOT NULL,
  `DataWylogowania` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `logowania`
--

INSERT INTO `logowania` (`ID`, `IDUzytkownika`, `DataZalogowania`, `DataWylogowania`) VALUES
(8, 23, '2023-03-28 10:07:49', '2023-04-09 21:21:33'),
(9, 23, '2023-03-28 13:38:07', '2023-04-09 21:21:33'),
(10, 23, '2023-03-28 14:41:32', '2023-04-09 21:21:33'),
(11, 23, '2023-03-28 14:41:53', '2023-04-09 21:21:33'),
(12, 23, '2023-03-28 14:56:11', '2023-04-09 21:21:33'),
(13, 23, '2023-03-29 11:57:20', '2023-04-09 21:21:33'),
(14, 23, '2023-03-30 08:47:07', '2023-04-09 21:21:33'),
(15, 34, '2023-03-30 17:56:15', '2023-03-30 18:06:33'),
(16, 23, '2023-03-30 18:08:04', '2023-04-09 21:21:33'),
(17, 23, '2023-03-31 09:18:48', '2023-04-09 21:21:33'),
(18, 23, '2023-03-31 11:00:09', '2023-04-09 21:21:33'),
(19, 23, '2023-03-31 11:01:25', '2023-04-09 21:21:33'),
(20, 23, '2023-03-31 14:54:31', '2023-04-09 21:21:33'),
(21, 23, '2023-04-03 11:33:36', '2023-04-09 21:21:33'),
(22, 23, '2023-04-03 13:42:05', '2023-04-09 21:21:33'),
(23, 23, '2023-04-04 11:54:07', '2023-04-09 21:21:33'),
(24, 23, '2023-04-05 13:45:28', '2023-04-09 21:21:33'),
(25, 23, '2023-04-05 13:50:36', '2023-04-09 21:21:33'),
(26, 23, '2023-04-05 14:31:06', '2023-04-09 21:21:33'),
(27, 23, '2023-04-07 11:57:08', '2023-04-09 21:21:33'),
(28, 23, '2023-04-07 12:50:12', '2023-04-09 21:21:33'),
(29, 23, '2023-04-09 19:27:21', '2023-04-09 21:21:33'),
(30, 23, '2023-04-09 19:27:47', '2023-04-09 21:21:33'),
(31, 23, '2023-04-09 19:28:22', '2023-04-09 21:21:33'),
(32, 23, '2023-04-09 19:29:45', '2023-04-09 21:21:33'),
(33, 23, '2023-04-09 19:30:33', '2023-04-09 21:21:33'),
(34, 23, '2023-04-09 19:40:41', '2023-04-09 21:21:33'),
(35, 23, '2023-04-09 19:44:59', '2023-04-09 21:21:33'),
(36, 23, '2023-04-09 19:46:51', '2023-04-09 21:21:33'),
(37, 23, '2023-04-09 19:50:40', '2023-04-09 21:21:33'),
(38, 23, '2023-04-09 19:53:50', '2023-04-09 21:21:33'),
(39, 23, '2023-04-09 19:55:08', '2023-04-09 21:21:33'),
(40, 23, '2023-04-09 19:56:06', '2023-04-09 21:21:33'),
(41, 23, '2023-04-09 20:08:32', '2023-04-09 21:21:33'),
(42, 23, '2023-04-09 20:22:51', '2023-04-09 21:21:33'),
(43, 23, '2023-04-09 20:34:09', '2023-04-09 21:21:33'),
(44, 23, '2023-04-09 20:43:36', '2023-04-09 21:21:33'),
(45, 23, '2023-04-09 21:21:53', '2023-04-09 21:21:53');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logumiejetnosci`
--

CREATE TABLE `logumiejetnosci` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `IDUmiejetnosci` int(11) NOT NULL,
  `Data` datetime NOT NULL,
  `Koszt` decimal(65,2) NOT NULL,
  `Poziom` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `logumiejetnosci`
--

INSERT INTO `logumiejetnosci` (`ID`, `IDUzytkownika`, `IDUmiejetnosci`, `Data`, `Koszt`, `Poziom`) VALUES
(4, 23, 1, '2023-03-22 09:41:13', '3.00', 0),
(5, 23, 2, '2023-03-22 09:41:13', '3.00', 0),
(6, 23, 3, '2023-03-22 09:41:13', '7.00', 0),
(13, 32, 1, '2023-03-23 13:51:04', '3.00', 0),
(14, 32, 2, '2023-03-23 13:51:04', '3.00', 0),
(15, 32, 3, '2023-03-23 13:51:04', '7.00', 0),
(16, 33, 1, '2023-03-25 20:15:42', '3.00', 0),
(17, 33, 2, '2023-03-25 20:15:42', '3.00', 0),
(18, 33, 3, '2023-03-25 20:15:42', '7.00', 0),
(19, 34, 1, '2023-03-30 17:56:03', '3.00', 0),
(20, 34, 2, '2023-03-30 17:56:03', '3.00', 0),
(21, 34, 3, '2023-03-30 17:56:03', '7.00', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `portfel`
--

CREATE TABLE `portfel` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `Monety` decimal(65,2) NOT NULL,
  `Bilety` int(11) NOT NULL,
  `Punkty` decimal(65,0) NOT NULL,
  `Swiat` int(11) NOT NULL,
  `Rozdzial` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `portfel`
--

INSERT INTO `portfel` (`ID`, `IDUzytkownika`, `Monety`, `Bilety`, `Punkty`, `Swiat`, `Rozdzial`) VALUES
(7, 23, '10.00', 0, '0', 0, 0),
(10, 32, '10.00', 0, '0', 0, 0),
(11, 33, '10.00', 0, '0', 0, 0),
(12, 34, '10.00', 0, '0', 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `statuskonta`
--

CREATE TABLE `statuskonta` (
  `ID` int(11) NOT NULL,
  `IDUzytkownika` int(11) NOT NULL,
  `StatusKonta` int(11) NOT NULL,
  `Grupa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `statuskonta`
--

INSERT INTO `statuskonta` (`ID`, `IDUzytkownika`, `StatusKonta`, `Grupa`) VALUES
(8, 23, 1, 3),
(12, 32, 1, 3),
(13, 33, 1, 3),
(14, 34, 1, 3);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `statusnazwa`
--

CREATE TABLE `statusnazwa` (
  `ID` int(11) NOT NULL,
  `Nazwa` varchar(255) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `statusnazwa`
--

INSERT INTO `statusnazwa` (`ID`, `Nazwa`) VALUES
(1, 'Aktywne'),
(2, 'Niekatywne'),
(3, 'Zablokowane'),
(4, 'Zawieszone'),
(5, 'Usuniete'),
(6, 'Niezweryfikowane');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `typdzialalnosci`
--

CREATE TABLE `typdzialalnosci` (
  `ID` int(11) NOT NULL,
  `Nazwa` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `CzasTrwania` decimal(65,0) NOT NULL,
  `WspolczynnikZarobku` decimal(65,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `typdzialalnosci`
--

INSERT INTO `typdzialalnosci` (`ID`, `Nazwa`, `CzasTrwania`, `WspolczynnikZarobku`) VALUES
(1, 'Rynek', '28800', '1.50');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `umiejetnosci`
--

CREATE TABLE `umiejetnosci` (
  `ID` int(11) NOT NULL,
  `Nazwa` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `MaxPoziom` int(11) NOT NULL,
  `WspolczynnikKosztu` decimal(65,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `umiejetnosci`
--

INSERT INTO `umiejetnosci` (`ID`, `Nazwa`, `MaxPoziom`, `WspolczynnikKosztu`) VALUES
(1, 'Atak', 0, '0.10'),
(2, 'Obrona', 0, '0.10'),
(3, 'Handel', 0, '0.20');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `Nazwa` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `Haslo` varchar(500) COLLATE utf8_polish_ci NOT NULL,
  `DataStworzenia` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `users`
--

INSERT INTO `users` (`ID`, `Nazwa`, `Email`, `Haslo`, `DataStworzenia`) VALUES
(23, 'Jacob', 'jacob@op.pl', '$2y$10$hma4LTVr4HWkTyQdyspPGO2BJo3hEE1ZgEfWe0G1hkgt/8f.QskUa', '2023-03-22 09:41:13'),
(32, 'Test', 'test@op.pl', '$2y$10$m1DDftRcVPoYV1WtY7B5h.LezVx.V.y/rI8i22eQDcg7FfLDJS6ei', '2023-03-23 13:51:04'),
(33, 'Maciej', 'maciej@op.pl', '$2y$10$AOWJ/YyA3tH5J6jU0tdmj.HQUk98e3rzryIKpbWsa53Cd0uCcQQje', '2023-03-25 20:15:42'),
(34, 'abc', 'abc@op.pl', '$2y$10$IJ1W4Ro4Rq0SLUgOvymyf.6MDrY3qwwL5PPAKgX0eoTEAwp6RYXLK', '2023-03-30 17:56:03');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `grupanazwa`
--
ALTER TABLE `grupanazwa`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `kodypolecenia`
--
ALTER TABLE `kodypolecenia`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `logdzialalnosci`
--
ALTER TABLE `logdzialalnosci`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `logowania`
--
ALTER TABLE `logowania`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `logumiejetnosci`
--
ALTER TABLE `logumiejetnosci`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `portfel`
--
ALTER TABLE `portfel`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `statuskonta`
--
ALTER TABLE `statuskonta`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `statusnazwa`
--
ALTER TABLE `statusnazwa`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `typdzialalnosci`
--
ALTER TABLE `typdzialalnosci`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `umiejetnosci`
--
ALTER TABLE `umiejetnosci`
  ADD PRIMARY KEY (`ID`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT dla zrzuconych tabel
--

--
-- AUTO_INCREMENT dla tabeli `grupanazwa`
--
ALTER TABLE `grupanazwa`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT dla tabeli `kodypolecenia`
--
ALTER TABLE `kodypolecenia`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT dla tabeli `logdzialalnosci`
--
ALTER TABLE `logdzialalnosci`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT dla tabeli `logowania`
--
ALTER TABLE `logowania`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT dla tabeli `logumiejetnosci`
--
ALTER TABLE `logumiejetnosci`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT dla tabeli `portfel`
--
ALTER TABLE `portfel`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT dla tabeli `statuskonta`
--
ALTER TABLE `statuskonta`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT dla tabeli `statusnazwa`
--
ALTER TABLE `statusnazwa`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT dla tabeli `typdzialalnosci`
--
ALTER TABLE `typdzialalnosci`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT dla tabeli `umiejetnosci`
--
ALTER TABLE `umiejetnosci`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT dla tabeli `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
