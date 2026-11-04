<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;

/**
 * Motywy tygodniowych wypraw. Kod motywu zapisuje sie w bazie, wiec NIE zmieniamy kodow ani nie przestawiamy kolejnosci
 * (nazwy i opisy mozna swobodnie poprawiac). Trzeci element to opcjonalna lista miesiecy, w ktorych motyw moze wypasc.
 */
final class SquadTrailThemes
{
    /** Motywy ogolne: dla kazdej specjalizacji. */
    private const GENERAL = [
        'g_lesna' => ['Leśna ścieżka', 'Cień drzew, miękka ściółka i cisza, którą przerywa tylko szelest liści.'],
        'g_woda' => ['Nad wodą', 'Trasa wzdłuż rzeki albo jeziora, z odbiciem nieba przy każdym zakręcie.'],
        'g_przelecz' => ['Przełęcz o świcie', 'Wczesny start i widok, który wynagradza każdy krok pod górę.'],
        'g_dolina' => ['Dolina we mgle', 'Poranna mgła przykrywa łąki, a oddział idzie jak przez sen.'],
        'g_polne' => ['Polne drogi', 'Szerokie pola, miedze i wiatr, który niesie zapach siana.'],
        'g_trakt' => ['Zapomniany trakt', 'Dawna droga, której nie ma na nowych mapach, ale wciąż prowadzi do celu.'],
        'g_wiosna' => ['Wiosenna włóczęga', 'Pierwsze kwiaty, wartkie strumienie i lekki plecak.', [3, 4, 5]],
        'g_lato' => ['Letnia wyprawa', 'Długi dzień, dużo słońca i postój przy każdym źródle.', [6, 7, 8]],
        'g_jesien' => ['Jesienne kolory', 'Rudy, złoty i czerwony las oraz zapach mokrych liści.', [9, 10, 11]],
        'g_zima' => ['Zimowy trakt', 'Skrzypiący śnieg, ciepła herbata w termosie i krótki dzień.', [12, 1, 2]],
    ];

    private const WINTER = [11, 12, 1, 2, 3];

    /** Motywy specjalizacji. */
    private const BY_SPEC = [
        'piesza' => [
            'piesza_1' => ['Wielka pętla', 'Jedna długa trasa, która kończy się tam, gdzie się zaczęła.'],
            'piesza_2' => ['Dzień bez asfaltu', 'Tylko ścieżki, drogi gruntowe i leśne przecinki.'],
            'piesza_3' => ['Marsz o zmierzchu', 'Wyruszacie późno, a ostatni odcinek oświetla zachodzące słońce.'],
            'piesza_4' => ['Przez trzy wsie', 'Szlak łączy sąsiednie wioski, a w każdej czeka studnia i ławka.'],
        ],
        'gorska' => [
            'gorska_1' => ['Grań w chmurach', 'Wąski grzbiet nad doliną i chmury tuż pod stopami.'],
            'gorska_2' => ['Szczyt przed burzą', 'Wyścig z czasem: wejść i zejść, zanim niebo ściemnieje.'],
            'gorska_3' => ['Perć nad doliną', 'Eksponowana ścieżka, która wymaga uwagi na każdym kroku.'],
            'gorska_4' => ['Schronisko za przełęczą', 'Cel jest wysoko, a nagrodą jest gorąca zupa i miejsce przy piecu.'],
        ],
        'rowerowa' => [
            'rowerowa_1' => ['Wzdłuż rzeki', 'Równa trasa rowerowa przy wodzie, idealna na długi dystans.'],
            'rowerowa_2' => ['Podjazd na przełęcz', 'Serpentyny, zadyszka i zjazd, który wynagradza wszystko.'],
            'rowerowa_3' => ['Leśne ścieżki', 'Korzenie, zakręty i szybkie zjazdy w cieniu drzew.'],
            'rowerowa_4' => ['Gravelowy objazd', 'Mieszanka asfaltu, szutru i polnych dróg w jednej wyprawie.'],
        ],
        'narciarska' => [
            'narciarska_1' => ['Biegówki o poranku', 'Świeżo przygotowana trasa i para unosząca się z oddechu.', self::WINTER],
            'narciarska_2' => ['Śnieżny trawers', 'Boczne podejście przez zasypane łąki do widokowej polany.', self::WINTER],
            'narciarska_3' => ['Podejście na fokach', 'Cicha wspinaczka w górę, zjazd po nietkniętym śniegu.', self::WINTER],
            'narciarska_4' => ['Zimowy biwak', 'Krótki dzień, długi wieczór i ognisko w śnieżnej ciszy.', self::WINTER],
        ],
        'jaskiniowa' => [
            'jaskiniowa_1' => ['Korytarze w ciemności', 'Latarki w dłoniach i echo własnych kroków.'],
            'jaskiniowa_2' => ['Studnia i komin', 'Pionowe odcinki, na których liczy się spokój i dobra asekuracja.'],
            'jaskiniowa_3' => ['Podziemna rzeka', 'Szum wody gdzieś w dole i ślady, które wyrzeźbiła przez tysiąclecia.'],
            'jaskiniowa_4' => ['Sala kryształów', 'Cel wyprawy: komora, w której światło odbija się od nacieków.'],
        ],
        'kajakowa' => [
            'kajakowa_1' => ['Leniwy spływ', 'Wolny nurt, wiosło miękko wchodzące w wodę i zielone brzegi.'],
            'kajakowa_2' => ['Bystrza i przenoska', 'Dynamiczny odcinek, a potem wspólne niesienie kajaków.'],
            'kajakowa_3' => ['Biwak nad rzeką', 'Dzień na wodzie, noc na brzegu przy małym ognisku.'],
            'kajakowa_4' => ['Jeziorny szlak', 'Cicha tafla, mewy i wyspy, do których płynie się godzinami.'],
        ],
        'zeglarska' => [
            'zeglarska_1' => ['Rejs pod żaglami', 'Pełny wiatr w żagle i horyzont bez końca.'],
            'zeglarska_2' => ['Cisza na jeziorze', 'Brak wiatru, więc cierpliwość i kubek herbaty na pokładzie.'],
            'zeglarska_3' => ['Wejście do portu', 'Cumowanie, liny i ciekawski komentarz z nabrzeża.'],
            'zeglarska_4' => ['Nocna wachta', 'Gwiazdy nad masztem i spokojny szum fali o burtę.'],
        ],
        'kolejowa' => [
            'kolejowa_1' => ['Parowóz na wąskim torze', 'Dym, gwizd i okno otwarte na wiatr.'],
            'kolejowa_2' => ['Stacje zapomnianej linii', 'Zarośnięte perony i dawne rozkłady jazdy.'],
            'kolejowa_3' => ['Panoramiczny przejazd', 'Pociąg wolno sunie przez mosty, tunele i doliny.'],
            'kolejowa_4' => ['Dworcowe skarby', 'Poszukiwanie starych kas, zegarów i latarni na prowincjonalnych dworcach.'],
        ],
        'podroznicza' => [
            'podroznicza_1' => ['Plecakiem przez granice', 'Jeden plecak, wiele języków i dużo nowych znajomych.'],
            'podroznicza_2' => ['Dziennik z drogi', 'Każdy dzień zapisany w notesie, który z czasem staje się mapą wspomnień.'],
            'podroznicza_3' => ['Targ w obcym mieście', 'Zapachy, kolory i pierwsza próba targowania się.'],
            'podroznicza_4' => ['Dalej niż mapa', 'Wyprawa tam, gdzie kończy się plan, a zaczyna ciekawość.'],
        ],
        'krajoznawstwo' => [
            'krajoznawstwo_1' => ['Ziemia, którą znamy', 'Odkrywanie okolicy, którą mijamy codziennie bez zatrzymania.'],
            'krajoznawstwo_2' => ['Kapliczki i krzyże przydrożne', 'Szlak śladami małych świadectw lokalnej historii.'],
            'krajoznawstwo_3' => ['Najciekawsze wsie regionu', 'Drewniane chaty, zdobione płoty i opowieści gospodarzy.'],
            'krajoznawstwo_4' => ['Lokalne legendy', 'Każdy przystanek ma swoją opowieść, a oddział zbiera je wszystkie.'],
        ],
        'historyczna' => [
            'historyczna_1' => ['Śladami dawnych bitew', 'Pola, wzgórza i pomniki, które pamiętają ważne chwile.'],
            'historyczna_2' => ['Ruiny zamku', 'Mury, baszty i widok z góry na dawne włości.'],
            'historyczna_3' => ['Szlak średniowiecznych grodów', 'Wały, kopce i drewniane palisady sprzed wieków.'],
            'historyczna_4' => ['Drogą kupców', 'Dawny szlak handlowy, którym wędrowały sól, bursztyn i wieści.'],
        ],
        'przyrodnicza' => [
            'przyrodnicza_1' => ['Poranek ptaków', 'Lornetka, cisza i lista gatunków do zaznaczenia.'],
            'przyrodnicza_2' => ['Łąka pełna kwiatów', 'Motyle, pszczoły i barwy, które trudno zliczyć.', [5, 6, 7, 8, 9]],
            'przyrodnicza_3' => ['Tropami zwierząt', 'Odciski łap w błocie i uważne czytanie śladów.'],
            'przyrodnicza_4' => ['Torfowisko', 'Mokra, tajemnicza kraina rosiczek, mchów i kładek nad bagnem.'],
        ],
        'kulturalna' => [
            'kulturalna_1' => ['Szlak muzeów', 'Kilka wystaw w jednej wyprawie i dużo do opowiedzenia przy kolacji.'],
            'kulturalna_2' => ['Letni festiwal', 'Muzyka, tańce i jarmark rzemiosła w małym miasteczku.', [6, 7, 8, 9]],
            'kulturalna_3' => ['Drewniane kościółki', 'Zabytkowa architektura ukryta wśród wsi.'],
            'kulturalna_4' => ['Murale i rzeźby', 'Spacer śladem sztuki, która wyszła na ulice.'],
        ],
        'miejska' => [
            'miejska_1' => ['Starówka krok po kroku', 'Brukowane uliczki, rynek i dziedzińce ukryte za bramami.'],
            'miejska_2' => ['Podwórka i zaułki', 'Miasto, którego nie widać z głównej ulicy.'],
            'miejska_3' => ['Dachy i widoki', 'Wieże, tarasy i punkty, z których miasto wygląda inaczej.'],
            'miejska_4' => ['Miasto nocą', 'Światła, odbicia w kałużach i cichnące ulice.'],
        ],
        'fotograficzna' => [
            'fotograficzna_1' => ['Złota godzina', 'Ciepłe światło tuż po wschodzie albo przed zachodem słońca.'],
            'fotograficzna_2' => ['Odbicia w wodzie', 'Szukanie idealnego kadru w spokojnej tafli.'],
            'fotograficzna_3' => ['Detale i zbliżenia', 'Liść, kamień, pajęczyna: mały świat w dużym powiększeniu.'],
            'fotograficzna_4' => ['Nocne niebo', 'Statyw, cierpliwość i gwiazdy nad ciemnym lasem.'],
        ],
        'survival' => [
            'survival_1' => ['Schron z gałęzi', 'Budowa noclegu z tego, co oferuje las.'],
            'survival_2' => ['Ognisko na deszczu', 'Sucha kora, drobne drewno i cierpliwość.'],
            'survival_3' => ['Orientacja bez mapy', 'Słońce, mech i gwiazdy zamiast nawigacji.'],
            'survival_4' => ['Sztuka węzłów', 'Liny, supły i rozwiązania, które trzymają.'],
        ],
        'kulinarna' => [
            'kulinarna_1' => ['Targ regionalny', 'Sery, miody, pieczywo i ludzie, którzy znają ich historię.'],
            'kulinarna_2' => ['Piknik na szlaku', 'Gotowy prowiant i widok, który smakuje najlepiej.'],
            'kulinarna_3' => ['Kuchnia polowa', 'Zupa w kociołku nad ogniskiem dla całego oddziału.'],
            'kulinarna_4' => ['Szlak serów i miodów', 'Wędrówka od gospodarstwa do gospodarstwa z degustacją na przystankach.'],
        ],
    ];

    /** @return array{code:string,name:string,description:string}|null */
    public static function get(string $code): ?array
    {
        $row = self::all()[$code] ?? null;
        return $row === null ? null : ['code' => $code, 'name' => $row[0], 'description' => $row[1]];
    }

    /** @return array<string,array{0:string,1:string,2?:list<int>}> */
    private static function all(): array
    {
        $all = self::GENERAL;
        foreach (self::BY_SPEC as $themes) {
            $all += $themes;
        }
        return $all;
    }

    /**
     * Wybiera motyw na tydzien: deterministycznie (ten sam wynik dla tego samego oddzialu i tygodnia),
     * z puli ogolnych i specjalizacji, z uwzglednieniem pory roku i bez powtorki z poprzedniego tygodnia.
     */
    public static function pick(int $squadId, string $spec, DateTimeImmutable $weekStart, ?string $previous): string
    {
        $month = (int) $weekStart->format('n');
        $pool = self::GENERAL;
        foreach (self::BY_SPEC[$spec] ?? [] as $code => $row) {
            $pool[$code] = $row;
        }
        $codes = [];
        foreach ($pool as $code => $row) {
            if (isset($row[2]) && !in_array($month, $row[2], true)) {
                continue;
            }
            if ($code === $previous) {
                continue;
            }
            $codes[] = $code;
        }
        sort($codes);
        $idx = crc32($squadId . '|' . $weekStart->format('Y-m-d')) % count($codes);
        return $codes[$idx];
    }
}
