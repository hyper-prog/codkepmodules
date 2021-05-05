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
        'News full body html' => 'Teljes hír html törzse',
        'Published on _publishdatetime_' => 'Közzétéve _publishdatetime_',
        'Upload a news' => 'Hír feltöltése',
    ]);
}
