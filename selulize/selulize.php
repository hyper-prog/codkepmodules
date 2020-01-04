<?php
/*  SelUlize module for CodKep - Makes UL-LI structure from select
 *
 *  Module name: selulize
 *  Dependencies:
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_selulize_before_start()
{
    add_js_file(codkep_get_path("selulize","web") . '/selulize.js');
    add_css_file(codkep_get_path("selulize","web") . '/selulize.css');
}
