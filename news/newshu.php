<?php
/*  CodKep news module translation - Hungarian
 *
 *  Module name: newshu
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_newshu_preinit()
{
    add_t_array('hu', [
        'Edit this news' => 'Ennek a hírnek a szerkesztése',
        'Delete this news' => 'Ennek a hírnek a törlése',
        'View newer news' => 'Újabb hírek megtekintése',
        'View older news' => 'Régebbi hírek megtekintése',
        'NewsId' => 'HírAz',
        'Edit news' => 'Hír szerkesztése',
        'Delete news' => 'Hír törlése',
        'News identifier' => 'Hír azonosító',
        'Headline' => 'Cím',
        'News path (location)' => 'Hír útvonala (webhelye)',
        'News summary body html' => 'Összefoglaló hír html törzse',
        'News summary template' => 'Összefoglaló hír sablon',
        'News full body html' => 'Teljes hír html törzse',
        'News full body template' => 'Teljes hír sablon',
        'Published on _publishdatetime_' => 'Közzétéve _publishdatetime_',
        'Upload a news' => 'Hír feltöltése',
        'No modification template' => 'Nem módosító sablon',
        'View' => 'Megtekint',
        'Preview of the summary' => 'Az összefoglaló előnézete',
        'There is no preview of summary' => 'Nincs előnézet az összefoglalóhoz',
        'Preview of the full body' => 'A teljes hír előnézete',
        'There is no preview of full body' => 'Nincs előnézet a teljes hírhez',
        'Parameters of summary line by line or separated by semicolon' => 'Az összefoglaló paraméterei soronként vagy pontosvesszővel elválasztva',
        'Parameters of full body line by line or separated by semicolon' => 'A teljes hír paraméterei soronként vagy pontosvesszővel elválasztva',
    ]);
}
