<?php
/*  RawQueries module hungarian translates
 *
 *  Module name: rawquerieshu
 *  Dependencies:
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_rawquerieshu_init()
{
    add_t_array('hu',[
        'Query' => 'Lekérdez',
        'Queries' => 'Lekérdezések',
        'Query sample' => 'Lekérdezési minta',
        'QueryResult' => 'LekerdezesEredmeny',
        'Download Excel XML' => 'Excel XML letöltése',
        'Edit query' => 'Lekérdezés szerkesztése',
        'Query time' => 'A lekérdezés ideje',
        'Query description' => 'Lekérdezés leírása',
        'Query from the system' => 'Lekérdezés a rendszerből',
        'Add a query to the system' => 'Lekérdezés felvétele a rendszerben',
        'Run a query in the system' => 'Lekérdezés futtatása a rendszerben',
        'There was an error executing query: _num_' => 'Hiba lépett fel a következő lekérdezés futtatásakor: _num_',
        'The number of the query' => 'A lekérdezés száma',
        'Security warning!' => 'Biztonsági figyelmeztetés!',
        'You can delete queries only with empty query string!' => 'A lekérdezéseket csak üres lekérdezési karakterlánccal törölheti!',
        'The specified query number is already present in the system! Please choose another number!' => 'A megadott lekérdezési szám már létezik a rendszerben! Kérjük, válasszon másik számot!',
        'New query (_num_)' => 'Új lekérdezés (_num_)',
        'Number/<br/>Serial' => 'Szám /<br/>Sorszám',
        'Parameter icon' => 'Parameter ikon',
        'The query needs parameters' => 'A lekérdezés paramétereket vár',
        'Show only favorite queries' => 'Csak a kedvencnek jelölt lekérdezések mutatása',
        'Add new query...' => 'Új lekérdezés felvétele...',
        'Mark as favorite' => 'Kedvencnek jelöl',
        'Remove from favorites list' => 'Levétel a kedvencek listájáról',
        'The query number: _num_' => 'A következő számú lekérdezés: _num_',
        '_num_ items listed.' => "_num_ elem listázva.",
        'Successfully saved.' => 'Sikeresen mentve.',
        'You do not have the required permissions to perform this operation!' => 'Nincs meg a szükséges jogosultsága ehhez a művelethez!',
        'Could not retrieve query!' => 'Nem sikerült beolvasni a lekérdezést!',
        'Clear parameter data' => 'Paraméter adatok törlése',
        'Parameters to be specified' => 'Megadandó paraméterek',
        'Parameter name' => 'Paraméter név',
        'Parameter description' => 'Paraméter leírás',
        'Parameter value' => 'Paraméter érték',
        'Set' => 'Beállít',
        'Trial run' => 'Próbafuttatás',
        'Close editor' => 'Szerkesztő bezárása',
        'Delete completely' => 'Teljes törlés',
        'Sample query, show only _snum_ results!' => 'Minta lekérdezés, csak _snum_ találat megjelenítése!',
        'Raw queries page' => 'Nyers lekérdezések oldala',
        'Set this feature' => 'A tulajdonság beállítása',
        'Value of the feature' => 'A tulajdonság értéke',
        'Keyword' => 'Kulcsszó',
        'Header cell text' => 'Fejléc cella szöveg',
        'Column background color' => 'Oszlop háttérszín',
        'Column width (excel)' => 'Oszlop szélesség (excel)',
        'Preview code' => 'Kód előnézet',
        'Cancel' => 'Mégsem',
        'Insert code' => 'Kód beszúrása',
        'Set field repository definiton...' => 'Mező definíciós elem beállítása...',
        'Set parameter properties...' => 'Paraméter tulajdonságok beállítása...',
        'Type of parameter' => 'Paraméter típusa',
        'Parameter substitution keyword' => 'Paraméter helyettesítő kulcsszó',
        'Parameter describe' => 'Paraméter leírás',
        'Available substitutes' => 'Használható helyettesítők',
        'Comma separated list of possible values # selected options-description pairs<br/>(value1#descr1,value2#descr2)' =>
            'Vesszővel felsorolt listája a # jellel elválasztott lehetséges érték-leírás pároknak<br/>(ertek1#Leírás1,ertek2#Leírás2)',
        'Initial value of the string' => 'A szöveg kezdőértéke',
        'Add parameter' => 'Paraméter felvétele',
        'Insert into the editor panel to the cursor position' => 'Beszúrás a kódszerkesztőbe a kurzorpozícióba',
        'Comma separated list of switchable # selected options-description pairs<br/>(value1#descr1,value2#descr2)' =>
            'Vesszővel felsorolt listája a # jellel elválasztott választható érték-leírás pároknak<br/>(ertek1#Leírás1,ertek2#Leírás2)',
        'Add new parameter...' => 'Új paraméter hozzáadása...',
        'Show/edit current (raw) parameter string...' => 'Aktuális (nyers) paraméter definíció megtekintése/szerkesztése...',
        'Full raw parameter definition text' => 'Teljes nyers paraméter definíció',
    ]);
}

