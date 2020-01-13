<?php
/*  The Mechanic theme for CodKep
 *
 *  Module name: mechanictheme
 *  Theme name: mechanic
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_mechanictheme_boot()
{
    global $mechanictheme;
    $mechanictheme = new stdClass();

    //Configurable values
    $mechanictheme->statustext = '';
    $mechanictheme->topbuttons = [];
    $mechanictheme->topbuttons_disable_loginlogoutbutton = false;
    $mechanictheme->disable_builtin_mechaniccss = false;
    $mechanictheme->disable_builtin_colorcss = false;

    $mechanictheme->dropdownmenu_structure_prefix = '';
    $mechanictheme->dropdownmenu_structure_suffix = '';

    $mechanictheme->dropdownmenu_startmenupics = codkep_get_path('mechanictheme','web') . '/images/menu.png';
    $mechanictheme->menu_add_samplemenu_if_empty = true;
}

function hook_mechanictheme_theme()
{
    $items = array();
    $items['mechanic'] = [
        'pageparts' => [
            "header",
            "footer",
        ],
        'generators' => [
            "runonce"   => "mechanictheme_runonce",
            "htmlstart" => "mechanictheme_htmlstart",
            "htmlend"   => "mechanictheme_htmlend",
            "body"      => "mechanictheme_body",
        ],
    ];

    return $items;
}

function hook_mechanictheme_init()
{
    global $site_config;
    global $mechanictheme;

    if(count($site_config->mainmenu) == 0 && $mechanictheme->menu_add_samplemenu_if_empty)
    {
        $site_config->mainmenu["Home"] = get_startpage();
        $site_config->mainmenu["Documentation"] = "doc/codkep";
    }
}

function mechanictheme_alwaysontop()
{
    global $user;
    global $mechanictheme;
    ob_start();

    print '<div class="dropdown float_left">';
    print '<span><img src="'.url($mechanictheme->dropdownmenu_startmenupics).'"></span>';
    print $mechanictheme->dropdownmenu_structure_prefix;
    print generate_menu_structure('  ','dropdown-content');
    print $mechanictheme->dropdownmenu_structure_suffix;
    print '</div>';

    print '<p class="statustextstyle float_left">'.$mechanictheme->statustext. '</p>';
    if(!$mechanictheme->topbuttons_disable_loginlogoutbutton)
    {
        if ($user->auth)
            print div("headerbutton logoutbtn float_right", l(t("Logout"), "user/logout", ["title" => t('Logout from the site')]));
        else
            print div("headerbutton logoutbtn float_right", l(t("Login"), "user/login", ["title" => t('Login to the site')]));
    }

    foreach($mechanictheme->topbuttons as $name => $to)
    {
        print div("headerbutton refreshbtn float_right",
              l($name,$to['url'], ['title' => $to['title']]));
    }

    print div('c','');
    return ob_get_clean();
}

// ===================================================================================================

function mechanictheme_runonce($content)
{
    global $mechanictheme;
    header('Content-Type: text/html; charset=utf-8');

    add_header('<meta http-equiv="Content-Type" content="Text/Html;Charset=UTF-8" />' . "\n");
    add_header('<meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n");
    add_header('<meta http-equiv="Cache-Control" content="no-cache" />' . "\n");
    add_header('<meta http-equiv="Pragma" content="no-cache" />' . "\n");

    $mypath = codkep_get_path('mechanictheme','web');
    if(!$mechanictheme->disable_builtin_mechaniccss)
        add_css_file($mypath . '/mechanictheme.css');
    if(!$mechanictheme->disable_builtin_colorcss)
        add_css_file($mypath . '/mechanictheme_colors.css');

    run_hook("mechanictheme_runonce"); //Possibility to add custom css and other stuff}
}

function mechanictheme_htmlstart($route)
{
    ob_start();
    print "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    return ob_get_clean();
}

function mechanictheme_htmlend($route)
{
    ob_start();
    print "</html>\n";
    return ob_get_clean();
}

function mechanictheme_body($content,$route)
{
    global $site_config;

    $startpage_rawurl = url($site_config->startpage_location);
    $home_text = t('Home');

    ob_start();
    print "<body>\n";
    print div('mechanicbaralwaysontopline',mechanictheme_alwaysontop());
    print "<!-- theme: mechanic -->";

    print div('headerplaceholder','');

    print " <div id=\"header\" class=\"headerbgcolor\">\n";
    print "  <div class=\"section c\">\n";

    if($site_config->site_name != NULL || $site_config->site_slogan != NULL)
    {
        print "   <div id=\"name-and-slogan\">\n";
        if($site_config->site_name != NULL)
        {
            print "    <div id=\"site-name\">\n";
            print "     <strong>\n";
            print "      <a href=\"$startpage_rawurl\" title=\"$home_text\" rel=\"home\">\n";
            print "       <span>".$site_config->site_name."</span>\n";
            print "      </a>\n";
            print "     </strong>\n";
            print "    </div>\n";
        }
        if($site_config->site_slogan != NULL)
        {
            print "    <div id=\"site-slogan\">\n";
            print "     ".$site_config->site_slogan."\n";
            print "    </div>\n";
        }
        print "   </div> <!-- #name-and-slogan --> \n";
    }

    print $content->pageparts['header'];
    print "  </div> <!-- .section -->\n";
    print " </div> <!-- #header -->\n";

    print "<div class=\"content\">\n";
    print $content->generated;
    print "</div>\n"; //content

    print " <div id=\"footer\" class=\"footerbgcolor\">\n";
    print "  <div class=\"section\">\n";
    print $content->pageparts['footer'];
    print "  </div> <!-- .section -->\n";
    print "  <div class=\"c\"></div>\n";
    print " </div> <!-- #footer -->\n";

    print "</body>\n";
    return ob_get_clean();
}

//end.
