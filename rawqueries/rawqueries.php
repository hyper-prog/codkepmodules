<?php
/*  RawQueries module for CodKep
 *
 *  Module name: rawqueries
 *  Dependencies: sql,user,node
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_rawqueries_boot()
{
    global $rawqueriesmodule_config;
    $rawqueriesmodule_config = new stdClass();

    //Configurable values
    $rawqueriesmodule_config->querymodules_pathprefix = 'queries';
    $rawqueriesmodule_config->querymodules_querypagetitle = '';
    $rawqueriesmodule_config->querymodules_actuatorcallback = 'rawquerymodule_default_actuator';
    $rawqueriesmodule_config->querymodules_qsqltablename = 'rawqueries_query';
    $rawqueriesmodule_config->querymodules_fsqltablename = 'rawqueries_qfav';
    $rawqueriesmodule_config->querymodules_samplelimit = 5;
    $rawqueriesmodule_config->querymodules_sampleactuatorcallback = 'rawquerymodule_default_sampleactuator';

    $rawqueriesmodule_config->add_rq_fav_to_usernode = true;
    $rawqueriesmodule_config->querymodules_get_favsett_callback = 'rawquerymodule_default_getfavoritesett';
    $rawqueriesmodule_config->querymodules_set_favsett_callback = 'rawquerymodule_default_setfavoritesett';
    $rawqueriesmodule_config->querymodules_skipfromlistcallback = '';
    $rawqueriesmodule_config->querymodules_runcounterbypasscallback = 'rawquerymodule_default_runcounterbypasscallback';


    //Non configurable items
    $rawqueriesmodule_config->querymodules_derivedptitle = '';
    $rawqueriesmodule_config->iconloc = '';
    $rawqueriesmodule_config->modulecssloc = '';
    $rawqueriesmodule_config->querymodules_extrafields_nt = [];
    $rawqueriesmodule_config->querymodules_extrafields_n = [];
}

function hook_rawqueries_init()
{
    global $rawqueriesmodule_config;
    $earr = run_hook('rawqueries_extrafields_sqlreq');
    foreach($earr as $n => $t)
    {
        if(strlen($n) > 0 && strlen($t) > 0) {
            $rawqueriesmodule_config->querymodules_extrafields_nt[$n] = $t;
            $rawqueriesmodule_config->querymodules_extrafields_n[] = $n;
        }
    }
}

function hook_rawqueries_before_start()
{
    global $site_config;
    global $rawqueriesmodule_config;

    $rawqueriesmodule_config->querymodules_derivedptitle = $site_config->site_name;
    if($rawqueriesmodule_config->querymodules_querypagetitle != '')
        $rawqueriesmodule_config->querymodules_derivedptitle = $rawqueriesmodule_config->querymodules_querypagetitle;
    if($rawqueriesmodule_config->querymodules_derivedptitle == '')
        $rawqueriesmodule_config->querymodules_derivedptitle = t('Queries');

    $rawqueriesmodule_config->iconloc = codkep_get_path('rawqueries','web') . '/icons';
    $rawqueriesmodule_config->modulecssloc = codkep_get_path('rawqueries','web') . '/rawqueries.css';
}

function hook_rawqueries_defineroute()
{
    global $rawqueriesmodule_config;

    $items = [];
    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/list",
        "callback" => "pc_rawqueriesmodule_queries",
    ];

    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/add",
        "callback" => "pc_rawqueriesmodule_queryadd",
    ];

    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/query/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
        ],
        "callback" => "pc_rawqueriesmodule_query",
    ];

    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqfavorite/{cmd}/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
            'cmd' => ['security' => 'text0nsne','source' => 'url','required' => 'Missing operation command!'],
        ],
        "callback" => "aj_rawqueriesmodule_ajaxqfavorite",
        "type" => "ajax",
    ];
    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryrunner/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
        ],
        "callback" => "aj_rawqueriesmodule_ajaxqueryrunner",
        "type" => "ajax",
    ];
    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryxml/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
        ],
        "callback" => "rw_rawqueriesmodule_ajaxqueryxml",
        "type" => "raw",
    ];

    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryeditor/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
        ],
        "callback" => "aj_rawqueriesmodule_ajaxqueryeditor",
        "type" => "ajax",
    ];
    $items[] = [
        "path" => $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryeditordoit/{num}",
        'parameters' => [
            'num' => ['security' => 'number0','source' => 'url','required' => 'The query ID must be provided!'],
        ],
        "callback" => "aj_rawqueriesmodule_ajaxqueryeditordoit",
        "type" => "ajax",
    ];
    return $items;
}

function rawquerymodulequery_access($num,$op,$account)
{
    if(!in_array($op,['create','delete','update','view','watchcounter']))
        return NODE_ACCESS_DENY;
    $na = run_hook('rawqueriesmodule_queryaccess',$num,$op,$account);
    if(in_array(NODE_ACCESS_DENY,$na))
        return NODE_ACCESS_DENY;
    if(in_array(NODE_ACCESS_ALLOW,$na))
        return NODE_ACCESS_ALLOW;

    //Default node permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return NODE_ACCESS_ALLOW;
    // Allows view for everyone who logged in. (You can disable by send DENY from a hook.)
    if($account->auth && $op == 'view')
        return NODE_ACCESS_ALLOW;
    return NODE_ACCESS_DENY;
}

function rawquerymodulequery_execaccess($querydataarray,$account)
{
    $na = run_hook('rawquerymodulequery_execaccess',$querydataarray,$account);
    if(in_array(NODE_ACCESS_DENY,$na))
        return NODE_ACCESS_DENY;
    if(in_array(NODE_ACCESS_ALLOW,$na))
        return NODE_ACCESS_ALLOW;

    //Default node permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return NODE_ACCESS_ALLOW;
    // Allows exec for everyone who logged in. (You can disable by send DENY from a hook.)
    if($account->auth)
        return NODE_ACCESS_ALLOW;
    return NODE_ACCESS_DENY;
}

function hook_rawqueries_required_sql_schema()
{
    global $rawqueriesmodule_config;

    $t = array();
    $startcols = [
        'typestr'    => "VARCHAR(4) DEFAULT 'n'",
        'targetstr'  => sql_t('longtext_type') . " NOT NULL DEFAULT ''",
        'sqlstrng'   => sql_t('longtext_type') . " DEFAULT ''",
        'num'        => "NUMERIC DEFAULT 0",
        'parameters' => "VARCHAR(256) DEFAULT ''",
        'enabled'    => "VARCHAR(1) DEFAULT 'e'",
        'runcounter' => "NUMERIC(5,0) DEFAULT 0",
        'modlogin'   => "VARCHAR(128)",
        'modtime'    => sql_t('timestamp_noupd'),
    ];
    $cols = array_merge($startcols,$rawqueriesmodule_config->querymodules_extrafields_nt);
    $t['rawqueries_module_rawqueries_table'] =
        [
            "tablename" => $rawqueriesmodule_config->querymodules_qsqltablename,
            "columns" => $cols,
        ];
    $t['rawqueries_module_rawqueries_favorite_table'] =
        [
            "tablename" => $rawqueriesmodule_config->querymodules_fsqltablename,
            "columns" => [
                'login'      => "VARCHAR(128)",
                'num'        => "NUMERIC",
            ],
        ];
    return $t;
}

function rawqueries_dm_listqueries($showdisabled = false,$favoritesonly = false)
{
    global $rawqueriesmodule_config;
    global $user;

    $q = db_query($rawqueriesmodule_config->querymodules_qsqltablename,'qry')
        ->get_a(['targetstr','num','parameters','enabled','runcounter','modlogin','modtime'],'qry')
        ->get_a($rawqueriesmodule_config->querymodules_extrafields_n,'qry')
        ->join_opt($rawqueriesmodule_config->querymodules_fsqltablename,'qfv',cond('and')
            ->ff(['qry','num'],['qfv','num'],'=')
            ->fv(['qfv','login'],$user->login,'='))
        ->get(['qfv','num'],'favnum')
        ->sort('num')
        ->sort('targetstr');
    if($favoritesonly)
        $q->cond_fnull(['qfv','num'],['opposite' => true]);
    if(!$showdisabled)
        $q->cond_fv('enabled','e','=');
    return $q->execute();
}

function rawqueries_dm_getquery_by_num($num)
{
    global $rawqueriesmodule_config;
    global $user;

    if($num == '')
        return NULL;
    return db_query($rawqueriesmodule_config->querymodules_qsqltablename,'qry')
        ->get_a(['targetstr','num','parameters','enabled','runcounter','sqlstrng','modlogin','modtime'],'qry')
        ->get_a($rawqueriesmodule_config->querymodules_extrafields_n,'qry')
        ->join_opt($rawqueriesmodule_config->querymodules_fsqltablename,'qfv',cond('and')
            ->ff(['qry','num'],['qfv','num'],'=')
            ->fv(['qfv','login'],$user->login,'='))
        ->get(['qfv','num'],'favnum')
        ->cond_fv(['qry','num'],$num,'=')
        ->execute_and_fetch();
}

function rawqueries_dm_savequery($num,$targetstr,$sqlstrng,$parameters,$enabled,$extrafields = [])
{
    global $user;
    global $rawqueriesmodule_config;

    db_update($rawqueriesmodule_config->querymodules_qsqltablename)
        ->set_fv_a([
            'targetstr' => $targetstr,
            'sqlstrng' => $sqlstrng,
            'parameters' => $parameters,
            'enabled' => $enabled,
            'modlogin' => ($user->auth ? $user->login : '<unauthenticated>'),
        ])
        ->set_fe('modtime',sql_t('current_timestamp'))
        ->set_fv_a($extrafields)
        ->cond_fv('num', $num, '=')
        ->execute();
}

function rawqueries_dm_set_query_runcounter($num,$runcounter)
{
    global $rawqueriesmodule_config;

    db_update($rawqueriesmodule_config->querymodules_qsqltablename)
        ->set_fv('runcounter',$runcounter)
        ->cond_fv('num', $num, '=')
        ->execute();
}

function rawqueries_dm_query_delete($num)
{
    global $rawqueriesmodule_config;

    $r = rawqueries_dm_getquery_by_num($num);
    if($r['sqlstrng'] != '')
    {
        load_loc('error', t('You can delete queries only with empty query string!'),
            t('Security warning!'));
        return;
    }
    db_delete($rawqueriesmodule_config->querymodules_qsqltablename)
        ->cond_fv('num',$num,'=')
        ->execute();
}

function rawqueries_dm_query_addnew($num)
{
    global $user;
    global $rawqueriesmodule_config;

    $db = db_query($rawqueriesmodule_config->querymodules_qsqltablename)
        ->counting('num','numcount')
        ->cond_fv('num',$num,'=')
        ->execute_and_fetch();
    if($db['numcount'] > 0)
    {
        load_loc('error',t('The specified query number is already present in the system! Please choose another number!'),
                         t('Security warning!'));
        return;
    }

    db_insert($rawqueriesmodule_config->querymodules_qsqltablename)
        ->set_fv_a([
            'num' => $num,
            'targetstr' => t('New query (_num_)',['_num_' => $num]),
            'sqlstrng' => '',
            'parameters' => '',
            'enabled' => 'd',
            'runcounter' => 0,
            'modlogin' => ($user->auth ? $user->login : '<unauthenticated>'),
            ])
        ->set_fe('modtime',sql_t('current_timestamp'))
        ->execute();
}

function rawqueries_dm_favorite_set($num)
{
    global $rawqueriesmodule_config;
    global $user;

    $c = db_query($rawqueriesmodule_config->querymodules_fsqltablename,'qfv')
        ->counting('num','numcount')
        ->cond_fv(['qfv','num'],$num,'=')
        ->cond_fv(['qfv','login'],$user->login,'=')
        ->execute_and_fetch();
    if(isset($c['numcount']) && $c['numcount'] < 1)
        db_insert($rawqueriesmodule_config->querymodules_fsqltablename)
            ->set_fv_a(['num' => $num, 'login' => $user->login])
            ->execute();
}

function rawqueries_dm_favorite_unset($num)
{
    global $rawqueriesmodule_config;
    global $user;

    db_delete($rawqueriesmodule_config->querymodules_fsqltablename)
        ->cond_fv('num',$num,'=')
        ->cond_fv('login',$user->login,'=')
        ->execute();
}

function hook_rawqueries_nodetype_alter_user($p)
{
    global $rawqueriesmodule_config;
    global $user_module_settings;

    if($rawqueriesmodule_config->add_rq_fav_to_usernode && $user_module_settings->define_user_nodetype)
    {
        $p->def['fields'][72] = [
            'sql' => 'rawqueries_favenable',
            'text' => 'Rawqueries show only favorites',
            'hide' => true,
            'type' => 'check',
            'sqlcreatetype' => 'BOOLEAN default FALSE',
        ];
    }
}

function rawquerymodule_default_getfavoritesett()
{
    global $rawqueriesmodule_config;
    global $user_module_settings;

    if($rawqueriesmodule_config->add_rq_fav_to_usernode && $user_module_settings->define_user_nodetype)
    {
        global $user;
        $r = db_query($user_module_settings->sql_tablename)
                ->get('rawqueries_favenable')
                ->cond_fv('login',$user->login,'=')
                ->execute_and_fetch();
        return $r['rawqueries_favenable'];
    }
    return false;
}

function rawquerymodule_default_setfavoritesett($qfav)
{
    global $rawqueriesmodule_config;
    global $user_module_settings;

    if($rawqueriesmodule_config->add_rq_fav_to_usernode && $user_module_settings->define_user_nodetype)
    {
        global $user;
        db_update($user_module_settings->sql_tablename)
            ->set_fe('rawqueries_favenable',$qfav ? 'TRUE' : 'FALSE')
            ->cond_fv('login',$user->login,'=')
            ->execute();
    }
}

function db_rawqueries_getqfav_settings()
{
    global $rawqueriesmodule_config;
    return call_user_func($rawqueriesmodule_config->querymodules_get_favsett_callback);
}

function db_rawqueries_setqfav_settings($qfav)
{
    global $rawqueriesmodule_config;
    call_user_func($rawqueriesmodule_config->querymodules_set_favsett_callback,$qfav);
}

function pc_rawqueriesmodule_queries()
{
    global $rawqueriesmodule_config;
    global $user;

    $qfav = false;
    if($user->auth)
    {
        $def_qfav = db_rawqueries_getqfav_settings();
        par_def("favoritesonly", "bool", 'all', false, $def_qfav ? 'on' : 'off');

        $qfav = $def_qfav;
        if(par_ex('favoritesonly'))
            $qfav = is_OnOff("favoritesonly");
    }

    ob_start();
    set_title($rawqueriesmodule_config->querymodules_derivedptitle . ' - ' .t("Query from the system"));
    add_css_file($rawqueriesmodule_config->modulecssloc);
    $r = rawqueries_dm_listqueries(NODE_ACCESS_ALLOW == rawquerymodulequery_access(0,'update',$user),$qfav);
    $watchcounter_perm = rawquerymodulequery_access(0,'watchcounter',$user);
    $c = [
        '#tableopts' => ['style' => 'background-color: #ccaa77; border-collapse:collapse;','class' => 'rawqueriesmainlist'],
        '#fields' => ['serialnum','fav','runcounter','targetstr','runner'],
        '#lineskip_callback' => function($r) {
            global $user;
            global $rawqueriesmodule_config;
            if(NODE_ACCESS_ALLOW != rawquerymodulequery_access($r['num'],'view',$user))
                return true;
            if($rawqueriesmodule_config->querymodules_skipfromlistcallback != '' &&
               is_callable($rawqueriesmodule_config->querymodules_skipfromlistcallback) &&
               call_user_func($rawqueriesmodule_config->querymodules_skipfromlistcallback,$r) == true)
                return true;
            return false;
        },
        '#lineoptions_callback' => function($r) {
            if($r['enabled'] != 'e')
                return ['style' => 'background-color: #888888;'];
            if($r['parameters'] != '')
                return ['style' => 'background-color: #bb9988;'];
            return [];
        },
        'serialnum' => [
            'headertext' => t('Number/<br/>Serial'),
            'cellopts' => ['style' => 'background-color: #ffffff;'],
            'valuecallback' => function($r) {
                return $r['num'].'/<small>'.$r['__rownumber__'].'</small>';
            }
        ],
        'fav' => [
            'skip' => !$user->auth,
            'headertext' => ':-)',
            'cellopts' => ['style' => 'background-color: #eeeeee;'],
            'valuecallback' => function($r) {
                if($r['favnum'] == '')
                    return '<div id="qn_'.$r['num'].'_e">'.
                    rawqueries_favmarker_linktext(false,$r['num'],true) .
                    '</div>';
                return '<div id="qn_'.$r['num'].'_e">'.
                rawqueries_favmarker_linktext(true,$r['num'],true) .
                '</div>';
            }
        ],
        'runcounter' => [
            'skip' => $watchcounter_perm == NODE_ACCESS_ALLOW ? false : true,
            'cellopts' => ['style' => 'background-color: #eeee00;'],
            'headertext' => '',
        ],
        'targetstr' => [
            'headertext' => t('Query description'),
            'cellopts' => ['style' => 'max-width: 777px; padding: 4px 6px 4px 2px;'],
            'valuecallback' => function($r) {
                global $rawqueriesmodule_config;
                return $r['targetstr'] .
                        ($r['parameters'] != '' ?
                            (' <img src="'.
                             url($rawqueriesmodule_config->iconloc.'/editicon.png').
                             '" border=0 alt="'.t('Parameter icon').'" title="'.t('The query needs parameters') .'">'
                            )
                            : '');
            }
        ],
        'runner' => [
            'headertext' => '',
            'valuecallback' => function($r) {
                global $rawqueriesmodule_config;
                return l(t('Query'),$rawqueriesmodule_config->querymodules_pathprefix . "/query/".$r['num'],
                         ['class' => 'rq_anim_popones rq_animhover_pops rawqueriesbtnlnk rq_width90']);
            }
        ],
    ];

    print '<h2>'.t('Query from the system').'</h2>';
    print join('',run_hook('rawqueriesmodule_querylist_print','beforeall'));

    if($user->auth)
        print put_OnOffSwitch(t('Show only favorite queries'),"favoritesonly",current_loc());

    print join('',run_hook('rawqueriesmodule_querylist_print','beforetable'));
    print to_table($r,$c);
    print join('',run_hook('rawqueriesmodule_querylist_print','aftertable'));

    if(NODE_ACCESS_ALLOW == rawquerymodulequery_access(0,'create',$user))
        print l(t('Add new query...'),$rawqueriesmodule_config->querymodules_pathprefix . "/add",['class' => 'rawqueries_smpllnk']);

    print join('',run_hook('rawqueriesmodule_querylist_print','afterall'));

    if($user->auth)
        if(($def_qfav && !$qfav) || (!$def_qfav && $qfav) )
            db_rawqueries_setqfav_settings($qfav ? true : false);

    return ob_get_clean();
}

function rawqueries_favmarker_linktext($f,$num,$fromlist = false)
{
    global $rawqueriesmodule_config;
    return
        l('<img src="'.url($rawqueriesmodule_config->iconloc.'/'.($f ? 'starin.png' : 'starout.png')).'" border=0 alt="Favorite icon">',
            $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqfavorite/".($f?'unset':'set').'/'.$num,
            ['class' => 'use-ajax rawqueries_smpllnk',
                'title' => $f ? t('Remove from favorites list') : t('Mark as favorite')],
            ($fromlist ? ['fromlist'=>'yes']:[]));
}

function aj_rawqueriesmodule_ajaxqfavorite()
{
    $num = par('num');
    $cmd = par('cmd');
    $h = '';
    par_def('fromlist','text0');
    if($cmd == 'set')
    {
        rawqueries_dm_favorite_set($num);
        $h = rawqueries_favmarker_linktext(true,$num,par_is('fromlist','yes'));
    }
    if($cmd == 'unset')
    {
        rawqueries_dm_favorite_unset($num);
        $h = rawqueries_favmarker_linktext(false,$num,par_is('fromlist','yes'));
    }

    if($cmd == 'unset' && db_rawqueries_getqfav_settings() && par_is('fromlist','yes'))
        ajax_add_refresh();
    else
        ajax_add_html('#qn_'.$num.'_e',$h);
}

function pc_rawqueriesmodule_query()
{
    global $rawqueriesmodule_config;
    global $user;
    $num = par('num');

    ob_start();
    set_title($rawqueriesmodule_config->querymodules_pathprefix . ' - ' . t("Run a query in the system"));
    add_css_file($rawqueriesmodule_config->modulecssloc);
    add_js_file(codkep_get_path("rawqueries","web") . "/rawqueries.js");
    $r = rawqueries_dm_getquery_by_num($num);

    print '<table><tr><td>';
    print '<h2 class="rq_float_left">' . t('The query number: _num_',['_num_' => $num]) . '</h2>';
    print '</td><td>';
    if($user->auth)
        print '<div id="qn_'.$num.'_e">'.
            rawqueries_favmarker_linktext($r['favnum'] == '' ? false: true,$num) .
            '</div>';
    print '</td></tr></table>';
    print "<div class=\"rq_qdescribe\">";
    print '<strong>'.$r['targetstr'].'</strong>';
    print "</div>";

    print "<div class=\"rq_qcontrolpanel\">";
    $ct = new HtmlTable('querycontroltable');

    print '<div class="query_editor_placeholder"></div>';

    $ppakk = [];
    $required_parameters_are_set = true;
    if($r['parameters'] != '')
    {
        $skip_load_values = false;
        par_def('sbmtsave','text2');
        if(par_is('sbmtsave',t('Clear parameter data')))
            $skip_load_values = true;
        $pf = new HtmlForm('qparam_form');
        $pf->action_get(current_loc());
        $pf->text('tt1','<table class="rq_qparamtablec">');
        $pf->text('tt10','<tr><th colspan="2">' . t('Parameters to be specified') . '</th></tr>');
        $pf->text('tt11','<tr><th>'.t('Parameter name').'</th><th>'.t('Parameter value').'</th></tr>');
        $parr = explode(';',$r['parameters']);
        foreach($parr as $p)
        {
            if($p == '')
                continue;
            $pt = explode(':',$p);
            if(count($pt) < 2)
                continue;
            $expltext = $pt[1];
            if(isset($pt[2]))
                $expltext = $pt[2];
            if($pt[0] == 'DATE')
            {
                $pf->datefield_p('date',$pt[1],date('Y-m-d'),[
                    'before' => '<tr><td>'.$expltext.'</td><td>',
                    'after' => '</td></tr>',
                    'no_par_load' => $skip_load_values]);

                if(!par_ex($pt[1].'_year' ) || !par_ex($pt[1].'_month') || !par_ex($pt[1].'_day') ||
                    par($pt[1].'_year') == '' || par($pt[1].'_month') == '' || par($pt[1].'_day') == '' ||
                    par($pt[1].'_year') == 1899 || par($pt[1].'_month') == 0 || par($pt[1].'_day') == 0)
                    $required_parameters_are_set = false;
                else
                    $ppakk[$pt[1]] = par($pt[1].'_year') . '-' . par($pt[1].'_month') . '-' . par($pt[1].'_day');
            }
            if($pt[0] == 'STRING')
            {
                $pf->input_p('text',$pt[1],'',[
                    'before' => '<tr><td>'.$expltext.'</td><td>',
                    'after' => '</td></tr>',
                    'no_par_load' => $skip_load_values]);
                if(!par_ex($pt[1]) || par($pt[1]) == '')
                    $required_parameters_are_set = false;
                else
                    $ppakk[$pt[1]] = par($pt[1]);
            }
        }

        if(!$required_parameters_are_set)
            $pf->input('submit','sbmtsave',t('Set'),[
                'before' => '<tr><td colspan="2">',
                'after' => '</td></tr>',
                'style' => 'margin:auto; display:block;']);
        else
            $pf->input('submit','sbmtsave',t('Clear parameter data'),[
                'before' => '<tr><td colspan="2">',
                'after' => '</td></tr>',
                'style' => 'margin:auto; display:block;']);
        $pf->text('tt2','</table>');
        print $pf->get($required_parameters_are_set);
    }

    $pstr = base64_encode(serialize($ppakk));

    if($required_parameters_are_set)
    {
        $ct->cell(l(t('Query'), $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryrunner/" . $num,
            ['class' => 'use-ajax rawqueriesbtnlnk rq_width200 hideonclick rq_anim_swing rq_animhover_swing', 'id' => 'ajaxqr_r'],
            ['p' => $pstr]));
        $ct->cell(l(t('Query sample'), $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryrunner/" . $num,
            ['class' => 'use-ajax rawqueriesbtnlnk rq_width200 hideonclick rq_anim_swing rq_animhover_swing', 'id' => 'ajaxqr_s'],
            ['p' => $pstr,'sample' => 'yes']));
        $ct->cell(l(t('Download Excel XML'), $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryxml/" . $num,
            ['class' => 'rawqueriesbtnlnk rq_width200 rq_anim_swing rq_animhover_swing '],
            ['p' => $pstr]));
    }
    if(NODE_ACCESS_ALLOW == rawquerymodulequery_access($num,'update',$user))
    {
        $ct->cell(l(t('Edit query'), $rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryeditor/" . $num,
            ['class' => 'use-ajax rawqueriesbtnlnk rq_width200 rq_anim_swing rq_animhover_swing ']));
    }

    print $ct->get();
    print "</div>";

    print "<div style='margin: auto; width: 200px;'><div class=\"rawqueries_twprogressanim\" style=\"display: none;\" id=\"aqproganim\"></div></div>";
    print "<br/>";
    print "<div id=\"qresdiv\"></div>";

    print "<script>
            jQuery(document).ready(function(){
                jQuery('.hideonclick').on('click',function(e) {
                    jQuery('.rq_qcontrolpanel').hide();
                    jQuery('#aqproganim').show();
                });
            });
          </script>";
    return ob_get_clean();
}

function aj_rawqueriesmodule_ajaxqueryeditor()
{
    global $rawqueriesmodule_config;
    global $user;
    global $field_repository;

    $num = par('num');
    if(NODE_ACCESS_ALLOW != rawquerymodulequery_access($num,'update',$user))
        return;

    $r = rawqueries_dm_getquery_by_num($num);
    ob_start();

    $frarea = '';
    $frarea .= '<div class="rq_fieldrepilist">';
    foreach($field_repository as $fridx => $frraw)
    {
        $tt = '';
        $bgc = '#dddddd';
        $fr = get_field_repository_definition($fridx);
        if(isset($fr['cellopts']['background-color']))
            $bgc = $fr['cellopts']['background-color'];
        if(isset($fr['headertext']) || isset($fr['description']))
            $tt = " title=\"" .
                (isset($fr['headertext']) ? $fr['headertext'] : '') . ':' .
                (isset($fr['description']) ? $fr['description'] : '')."\"";
        $frarea .= "<div class=\"rq_fieldrepi\" style=\"background-color: $bgc;\" $tt>$fridx</div>";
    }
    $frarea .= "<div class=\"rq_clearboth\"></div>";
    $frarea .= '</div>';

    $cf = new HtmlForm('qeditform');
    $cf->opts(['id' => 'qeditformidentifier']);
    $cf->action_ajax($rawqueriesmodule_config->querymodules_pathprefix . "/ajaxqueryeditordoit/".$num);
    $cf->text('t1','<table>');
    run_hook('rawqueries_extrafields_form','pos0',$cf,$r);
    $cf->text('tr_p1b','<tr><td>');
    $cf->select('select','qenable',$r['enabled'],['e' => t('Enabled'),'d' => t('Disabled')],['before' => '','after' => '']);
    run_hook('rawqueries_extrafields_form','pos1',$cf,$r);
    $cf->text('tr_p1e','</td></tr>');
    $cf->textarea('dscr',$r['targetstr'],3,100,['before' => '<tr><td>','after' => '</td></tr>']);
    run_hook('rawqueries_extrafields_form','pos2',$cf,$r);
    $cf->input('text','pars',$r['parameters'],
                ['size' => 75,
                 'before' => '<tr><td>'.t('Parameters').': ',
                 'after' => ' <small>(STRING|DATE:'.t('Parameter name').':'.t('Parameter description').')</small></td></tr>']);
    run_hook('rawqueries_extrafields_form','pos3',$cf,$r);
    $cf->text('fr',$frarea,['before' => '<tr><td>','after' => '</td></tr>']);
    $cf->textarea('qsql',$r['sqlstrng'],12,100,['id' => 'qe_qsql','before' => '<tr><td>','after' => '</td></tr>']);
    $cf->hidden('what','',['id' => 'todowhat']);
    run_hook('rawqueries_extrafields_form','pos4',$cf,$r);
    $cf->input('submit','qesmts',t('Trial run'),[
        'before' => '<tr><td>',
        'after' => '',
        'onclick' => "jQuery('#todowhat').val('tryrun');"]);
    $cf->input('submit','qesmts',t('Save'),[
        'before' => '',
        'after' => '',
        'onclick' => "jQuery('#todowhat').val('save');"]);
    $cf->input('submit','qesmts',t('Close editor'),[
        'before' => '',
        'after' => '',
        'onclick' => "jQuery('#todowhat').val('close');"]);
    $cf->input('submit', 'qesmts', t('Delete completely'), ['onclick' => "jQuery('#todowhat').val('delete');"]);

    $cf->text('tr2','</td></tr>');

    run_hook('rawqueries_extrafields_form','pos5',$cf,$r);
    $lst_mod_txt = t('Unknown');
    if(strlen($r['modtime']) > 0 && strlen($r['modlogin']) > 0)
        $lst_mod_txt = substr($r['modtime'],0,19).' ('.$r['modlogin'].')';
    $cf->text('lmodtxt',
              '<small>'.t('Last modified').': <span id="qe_lmodvals">'.$lst_mod_txt.'</span></small>',
              ['id' => 'qe_lmodtxt','before' => '<tr><td>','after' => '</td></tr>']);
    $cf->text('t2','</table><br/>');
    print '<div class="rq_qeblock">';
    print $cf->get();
    print '</div>';
    print "<script>
            jQuery(document).ready(function() {
                jQuery('.rq_fieldrepi').on('click',function(e) {
                    var s = jQuery(this).html();
                    if(s != '')
                        rawQEditorInsertAtCaret('qe_qsql','#'+jQuery(this).html());
                });
            });
        </script>";
    ajax_add_html('.query_editor_placeholder',ob_get_clean());
}

function aj_rawqueriesmodule_ajaxqueryeditordoit()
{
    global $rawqueriesmodule_config;
    global $user;

    form_source_check();

    par_def('qsql','free');
    par_def('qesmts','text2ns');
    par_def('what','text2ns');
    par_def('qenable','text0nsne');
    par_def('dscr','text5');
    par_def('pars','text5');

    $num = par('num');
    if(NODE_ACCESS_ALLOW != rawquerymodulequery_access($num,'update',$user))
    {
        ajax_add_alert(t('You do not have the required permissions to perform this operation!'));
        return;
    }

    $r = rawqueries_dm_getquery_by_num($num);
    if(!isset($r['targetstr']) || !isset($r['num']) || $r['num'] == '')
    {
        ajax_add_alert(t('Could not retrieve query!'));
        return;
    }

    if(par_is('what','tryrun'))
    {
        if(NODE_ACCESS_ALLOW != rawquerymodulequery_execaccess($r,$user))
        {
            ajax_add_alert(t('You do not have the required permissions to perform this operation!'));
            return;
        }

        $sql = par('qsql');
        $out = generate_rawqueries_output($sql,$num,'',true);
        ajax_add_hide('#aqproganim', '');
        ajax_add_html('#qresdiv', $out);
        ajax_add_show('.rq_qcontrolpanel', '');
        return;
    }
    if(par_is('what','save'))
    {
        $sqlstrng = par('qsql');
        $targetstr = par('dscr');
        $parameters = par('pars');
        $enabled = par('qenable');

        $extrafields = [];
        $ef = run_hook('rawqueries_extrafields_save');
        foreach($ef as $efn => $efv)
            if(in_array($efn,$rawqueriesmodule_config->querymodules_extrafields_n))
                $extrafields[$efn] = $efv;
        rawqueries_dm_savequery($num,$targetstr,$sqlstrng,$parameters,$enabled,$extrafields);
        ajax_add_html('#qe_lmodvals',t('Just now'));
        ajax_add_alert(t('Successfully saved.'));
    }
    if(par_is('what','close'))
    {
        ajax_add_html('.query_editor_placeholder','');
        return;
    }
    if(par_is('what','delete'))
    {
        rawqueries_dm_query_delete($num);
        ajax_add_goto($rawqueriesmodule_config->querymodules_pathprefix . "/list");
        return;
    }
}

function rawquerymodule_default_actuator($sql,$parameters,$errormessage)
{
    return sql_exec_fetchAll($sql,$parameters,$errormessage,true);
}

function rawquerymodule_default_sampleactuator($sql)
{
    global $rawqueriesmodule_config;
    $sql = preg_replace('/;?\s*$/','',$sql);
    $sql = preg_replace('/ORDER BY.*$/i','',$sql);
    $sql .= " limit " . $rawqueriesmodule_config->querymodules_samplelimit;
    return $sql;
}

function generate_rawqueries_output($sql,$num,$title,$html_xml = true)
{
    global $rawqueriesmodule_config;
    if($sql == '')
        return '';

    $pass = new stdClass();
    $pass->num = $num;
    $pass->html_xml = $html_xml;
    $pass->sqlref = &$sql;
    run_hook('rawqueries_executequery_alter',$pass);

    par_def('p','textbase64');
    $pstr = par('p');
    $ppack = unserialize(base64_decode($pstr));

    if($ppack !== null && is_array($ppack))
        foreach($ppack as $name => $value)
            $sql = str_replace($name,$value,$sql);

    $sql = preg_replace('/\s\#([:\#a-zA-Z_0-9]+)/',' "#${1}" ',$sql);
    $qresult = call_user_func($rawqueriesmodule_config->querymodules_actuatorcallback,
                              $sql,[],t('There was an error executing query: _num_',['_num_' => $num]));

    $x = null;
    if($html_xml)
    {
        $x = new HtmlTable('query_h_' . $num);
        $x->opts(['border' => 1,'style' => 'border-collapse: collapse;']);
    }
    else
    {
        $x = new ExcelXmlDocument('query_x_' . $num);
        $x->cell($title, ['size' => 12, 'strong' => 'yes', 'colspan' => '20']);
        $x->nrows(1);
        $x->cell(t('Query time') . ': ' . date('Y-m-d'), ['colspan' => '20']);
        $x->nrows(2);
    }

    $c = [
        '#return_disabled' => true,
        '#output_object' => 'table',
        '#default_headeropts' => [
            'type' => 'uni',
            't' =>'str',
            'horizontal' => 'center',
            'background-color' => '#626262',
            'color' => '#ffffff',
            'border' => 'all',
        ],
        '#default_cellopts' => [
            'type' => 'uni',
            't' =>'str',
            'background-color' => '#eeeeee',
            'border' => 'all',
        ],
    ];

    if(!$html_xml)
    {
        $c['#output_object'] = 'excelxml';
        header('Content-Type:application/xml');
        header('Content-Disposition: attachment; filename="'.
                $rawqueriesmodule_config->querymodules_derivedptitle . '_' . $num . '_' .
                t('QueryResult').'_'.date('Y-m-d').'.xml"');
    }

    $tresults = ["target" => $x];
    to_table($qresult,$c,$tresults);

    return $x->get() . ($html_xml ? t('_num_ items listed.',['_num_' => $tresults['rowcount']]) : '');
}

function aj_rawqueriesmodule_ajaxqueryrunner()
{
    global $rawqueriesmodule_config;
    global $user;

    par_def('sample','text0');
    $num = par('num');

    if(NODE_ACCESS_ALLOW != rawquerymodulequery_access($num,'view',$user))
    {
        ajax_add_hide('#aqproganim','');
        ajax_add_html('#qresdiv',t('You do not have the required permissions to perform this operation!'));
        ajax_add_show('.rq_qcontrolpanel','');
        return;
    }
    $r = rawqueries_dm_getquery_by_num($num);
    if(NODE_ACCESS_ALLOW != rawquerymodulequery_execaccess($r,$user))
    {
        ajax_add_hide('#aqproganim','');
        ajax_add_html('#qresdiv',t('You do not have the required permissions to perform this operation!'));
        ajax_add_show('.rq_qcontrolpanel','');
        return;
    }

    if(!isset($r['targetstr']) || $r['targetstr'] == '')
    {
        ajax_add_hide('#aqproganim','');
        ajax_add_html('#qresdiv',t('There was an error executing query: _num_',['_num_' => $num]));
        ajax_add_show('.rq_qcontrolpanel','');
        return;
    }

    $header = '';
    $sql = $r['sqlstrng'];
    if(par_is('sample','yes'))
    {
        $header = '<div style="color: #aa2222; font-weight: bold; font-size: 150%;">'.
                  t('Sample query, show only _snum_ results!',
                      ['_snum_' => $rawqueriesmodule_config->querymodules_samplelimit]).
                  '</div>';
        $sql = call_user_func($rawqueriesmodule_config->querymodules_sampleactuatorcallback,$sql);
    }
    $out = generate_rawqueries_output($sql,$num,$r['targetstr'],true);

    if(!call_user_func($rawqueriesmodule_config->querymodules_runcounterbypasscallback,$num))
        rawqueries_dm_set_query_runcounter($num,$r['runcounter'] + 1);

    ajax_add_hide('#aqproganim','');
    ajax_add_html('#qresdiv',$header.$out);
    ajax_add_show('.rq_qcontrolpanel','');
}

function rawquerymodule_default_runcounterbypasscallback($num)
{
    global $user;
    if($user->role == ROLE_ADMIN)
        return true;
    return false;
}

function rw_rawqueriesmodule_ajaxqueryxml()
{
    global $user;

    $num = par('num');
    if(NODE_ACCESS_ALLOW != rawquerymodulequery_access($num,'view',$user))
        return;
    $r = rawqueries_dm_getquery_by_num($num);
    if(NODE_ACCESS_ALLOW != rawquerymodulequery_execaccess($r,$user))
        return;
    if(!isset($r['targetstr']) || $r['targetstr'] == '')
        return;

    $sql = $r['sqlstrng'];
    return generate_rawqueries_output($sql,$num,$r['targetstr'],false);
}

function pc_rawqueriesmodule_queryadd()
{
    global $rawqueriesmodule_config;
    global $user;

    if(NODE_ACCESS_ALLOW != rawquerymodulequery_access(0,'create',$user))
        return '';

    ob_start();
    set_title($rawqueriesmodule_config->querymodules_derivedptitle . ' - ' . t("Add a query to the system"));
    add_css_file($rawqueriesmodule_config->modulecssloc);
    $f = new HtmlForm('create_rawqueries_form');
    $f->action_post(current_loc());
    $f->text('s1','<span class="rq_niceinputblock">');
    $f->input_p('text','number','',['id'=>"rq_ni_tline"]);
    $f->input_p('submit','crt',t('Add'),['id'=>"rq_ni_btn"]);
    $f->text('s2','</span>');
    print '<h2>' . t('Add new query...') . '</h2>';
    print t("The number of the query").':';
    if(par_is('crt',t('Add')) && par_ex('number') && !par_is('number',''))
    {
        $num = par('number');
        rawqueries_dm_query_addnew($num);
        goto_loc($rawqueriesmodule_config->querymodules_pathprefix . "/query/".$num);
    }
    print $f->get();
    return ob_get_clean();
}

function hook_rawqueries_introducer()
{
    $html = '';
    global $db;
    global $rawqueriesmodule_config;

    if(isset($db->open) && $db->open &&
        sql_table_exists($rawqueriesmodule_config->querymodules_qsqltablename) &&
        sql_table_exists($rawqueriesmodule_config->querymodules_fsqltablename))
    {
            $html .= l(t('Raw queries page'),$rawqueriesmodule_config->querymodules_pathprefix . "/list").'<br/>';
    }
    return ['RawQueries' => $html];
}
//end.