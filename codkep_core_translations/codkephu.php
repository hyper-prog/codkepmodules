<?php

/*  CodKep core translation - Hungarian
 *
 *  Module name: codkephu
 *  Dependencies:
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_codkephu_init()
{
    add_t_array('hu',[
        'Yes' => 'Igen',
        'No' => 'Nem',
        'Save' => 'Ment',
        'Delete' => 'Töröl',
        'Add' => 'Hozzáad',
        'Error' => 'Hiba',
        'Permission denied' => 'Hozzáférés megtagadva',
        'Login' => 'Bejelentkezés',
        'Logout' => 'Kijelentkezés',
        'Username' => 'Felhasználói név',
        'Password' => 'Jelszó',
        'Not found' => 'Nem található',
        'Enabled' => 'Engedélyezve',
        'Disabled' => 'Tíltva',
        'Parameters' => 'Paraméterek',
        'Location not found' => 'Hely nem található',
        'Page or content not found' => 'Lap vagy tartalom nem található',
        'Parameter security error' => 'Paraméter biztonsági hiba',
        'Parameter "_name_" does not match with type "_type_"',
        'A "_name_" nevű paraméter nem elégíti ki a "_type_" típus által támasztott követelményeket',
        'Sql connection error' => 'Sql csatlakozási hiba',
        'Sql error' => 'Sql hiba',
        'Last SQL command' => 'Utolsó SQL parancs',
        'Backtrace' => 'Hívási lánc',
        'Unknown' => 'Ismeretlen',
        'Expired form error' => 'Lejárt űrlap hiba',
        'The form is expired. Please refresh the page!' => 'Az űrlap érvénytelen lett. Kérlek frissítsd a lapot!',
        '(The form is loaded _msv_ but modified _csv_)' => '(Az űrlap _msv_ kor töltődött be, de _csv_ kor módosítva lett)',
        'Validation error on field: _field_ (_value_)' => 'Megfelelőségi hiba a _field_ mezőnél. (_value_)',
        'Field "_field_" cannot be empty' => 'A "_field_" mező nem lehet üres',
        'Form validation error' => 'Űrlap ellenőrzési hiba',
        'The "_field_" field is lower than minimum' => 'A "_field_" mező értéke alacsonyabb mint a minimum',
        'The "_field_" field is higher than maximum' => 'A "_field_" mező értéke magasabb mint a maximum',
        'Missing parameter error' => 'Hiba: Hiányzó paraméter',
        'This page require the parameter "_name_" <br/>_text_' => 'Ez az oldal elvár egy "_name_" paramétert <br/>_text_',
        'Undefined parameter error' => 'Definiálatlan paraméter hiba',
        'This page try to access a parameter which is undefined "_name_"' => 'Ez a lap próbál hozzáférni a "_name_" paraméterhez, ami nincs definiálva',
        'The uploaded file type is "_upltype_" not in "_reqtype_". Please upload the allowed kind of file' => 'A feltöltött fájl típusa "_upltype_" ami nincs rajta az engedélyezett "_reqtype_" típusok listáján. Kérlek csak engedélyezett típusú fájlokat tölts fel!',
        'The uploaded file type is not image file. Please upload image file' => 'A feltöltött fájl nem képfájl. Kérlek csak képfájlokat tölts fel!',
        'Node creating requested with unknown node type: "_unktype_"' => 'Node létrehozási utasítás történt ismeretlen node-on: "_unktype_"',
        'Unknown type error' => 'Hiba: Ismeretlen típus',
        'Value set request received for an uninitialized (typeless) node: "_namereq_"' => 'Adatmódosítási kérelem érkezett nem betöltött (típus nélküli) node-ra: "_namereq_"',
        'Uninitialized node set error' => 'Nem betöltött node adat változtatás hiba',
        'Value request for an uninitialized (typeless) node: "_namereq_"' => 'Adatolvasási kérelem érkezett nem betöltött (típus nélküli) node-ra:"_namereq_"',
        'Value request for an uninitialized (typeless) node!' => 'Adatolvasási kérelem érkezett nem betöltött (típus nélküli) node-ra!',
        'Uninitialized node error' => 'Hiba: Nem betöltött node',
        'Node insert request for an uninitialized (typeless) node!' => 'Node létrehozási kérelem nem betöltött (típus nélkuli) node-on!',
        'Node loading requested with unknown node type: "_unktype_"' => 'Node betöltési kérelem ismeretlen típusú node-ra: "_unktype_"',
        'Node load_intype requested with unknown node type: "_unktype_"' => 'Típusos node betöltési kérelem ismeretlen típusú node-ra: "_unktype_"',
        'Node save requested on uninitialized node!' => 'Node mentési kérelem nem betöltött node-on!',
        'The requested node is not found' => 'A kért node nem található a rendszerben',
        'You don\'t have the required permission to access this node' => 'Nincs meg a megfelelő jogosultságod, hogy a kért node-hoz hozzáférj',
        'You don\'t have the required permission to delete this node' => 'Nincs meg a megfelelő jogosultságod, hogy a kért node-ot törölhesd',
        'You don\'t have the required permission to create this node' => 'Nincs meg a megfelelő jogosultságod, hogy a kért node-hoz létrehozd',
        'You don\'t have the required permission to remove this file' => 'Nincs meg a megfelelő jogosultságod, hogy a kért fájlt törölhesd',
        'You don\'t have the required permission to create the file' => 'Nincs meg a megfelelő jogosultságod, hogy a létrehozd a fájlt',
        'Login disabled' => 'Bejelentkezés letíltva',
        'Failed to log in! Wrong user name or password.' => 'Bejelentkezés nem sikerült! Rossz a felhasználó név vagy a jelszó.',
        'Password security warning' => 'Jelszó biztonsági figyelmeztetés',
        'The password has to be at least _len_ character long!' => 'A megadott jelszó hossza minimum _len_ karakter kell legyen!',
        'The password has to contains at least _len_ lowercase letter!' => 'A megadott jelszó tartalmazzon legalább _len_ kisbetűt!',
        'The password has to contains at least _len_ uppercase letter!' => 'A megadott jelszó tartalmazzon legalább _len_ nagybetűt!',
        'The password has to contains at least _len_ numeric letter!' => 'A megadott jelszó tartalmazzon legalább _len_ számjegyet!',
        'The complexity of the password is too low!' => 'A megadott jelszó bonyolultsága túlságosan kicsi!',
        'Just now' => 'Éppen most',
        'Last modified' => 'Utolsó módosítás',
        'Delete this comment' => 'Töröld ezt a megjegyzést',
        'Send' => 'Elküld',
        'The "_username_" user logged in' => 'A "_username_" nevű felhasználó bejelentkezve',
        'Change my password' => 'Jelszavam megváltoztatása',
        'Nobody logged in' => 'Senki sincs bejelentkezve',
        'Whoami?' => 'Kivagyok?',
        'Startpage' => 'Nyitóoldal',
        'Warning!' => 'Figyelem!',
        'The given old password is wrong!' => 'A megadott régi jelszó hibás!',
        'Add page' => 'Lap felvétele',
        'Node types' => 'Node típusok',
        "You've already cast your vote." => 'Már leadtad a szavazatod.',
        'The client is blocked due to previous errors!' => 'Az ügyfél korábbi hibák miatt blokkolva van!',
        'The vote is not active.' => 'A szavazás nem aktív.',
        'You can not vote, because the vote is not started yet!' => 'Nem szavazhatsz, mert a szavazás még nem kezdődött el!',
        'You can not vote, because the vote is already expired!' => 'Nem szavazhatsz, mert a szavazás már lejárt!',
        "You don't have the necessary permission to view this vote." => 'Nincs elegendő jogosultságod a szavazás megtekintéséhez.',
        'Login to the site' => 'Bejelentkezés a weboldalra',
        'Logout from the site' => 'Kijelentkezés a webhelyről',
        'Unauthenticated user' => 'Nem hitelesített felhasználó',
    ]);

    add_t_array('hu',[
        'January' => 'Január',
        'February' => 'Február',
        'March' => 'Március',
        'April' => 'Április',
        'May' => 'Május',
        'June' => 'Június',
        'July' => 'Július',
        'August' => 'Augusztus',
        'September' => 'Szeptember',
        'October' => 'Október',
        'November' => 'November',
        'December' => 'December',
    ]);

}
//end.
