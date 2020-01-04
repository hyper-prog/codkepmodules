<?php
/*  ActivityPoll module for CodKep
 *
 *  Module name: activitypoll
 *  Dependencies: sql,user,forms
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

define('ACTIVITYPOLL_ACCESS_IGNORE',0);
define('ACTIVITYPOLL_ACCESS_ALLOW',1);
define('ACTIVITYPOLL_ACCESS_DENY',2);

function hook_activitypoll_boot()
{
    global $site_config;
    $site_config->poll_containers    = [];

    $site_config->activity_poll_main_css_class = 'ckpoll_default_style';
    $site_config->activity_poll_showpoll_callback = 'pollresult_generator_default';
}

function hook_activitypoll_before_start()
{
    add_css_file(codkep_get_path('activitypoll','web') . '/activitypoll.css');
}

function register_poll_container($containername)
{
    global $site_config;
    if(check_str($containername,'text0nsne'))
    {
        if(!in_array($containername, $site_config->poll_containers))
            array_push($site_config->poll_containers, $containername);
        return;
    }
    load_loc('error','Illegal poll container class (text0nsne allowed)','Internal activity module error');
}

function hook_activitypoll_defineroute()
{
    return [
        ['path' => 'votepollajax'             ,'callback' => 'aj_addvote_poll'       ,'type' => 'ajax'],
        ['path' => 'showpoll/{pollname}/{id}' ,'callback' => 'showpollpage_callback' ],
    ];
}

function register_poll($pollname,$container,$title,$choices,$default = '',$date_start = '',$date_end = '')
{
    $cnt = db_query('poll_parameters')
        ->counting()
        ->cond_fv('name',$pollname,'=')
        ->execute_to_single();
    if($cnt > 0)
        return false;

    $ii = db_insert('poll_parameters')
            ->set_fv_a([
                'name' => $pollname,
                'container' => $container,
                'titletext' => $title,
                'defidx' => $default
            ]);
    if($date_start != '')
    {
        if(!check_str($date_start,'isodate'))
            throw new Exception("The date_start parameter of register_poll() has wrong format. Only isodate accepted");
        $ii->set_fv('dstart',$date_start);
    }
    if($date_end != '')
    {
        if(!check_str($date_end,'isodate'))
            throw new Exception("The date_end parameter of register_poll() has wrong format. Only isodate accepted");
        $ii->set_fv('dend',$date_end);
    }

    sql_transaction();
    $ii->execute();
    foreach($choices as $i => $v)
    {
        if(strlen($i) > 5)
        {
            sql_rollback();
            throw new Exception("The choice index defined with register_poll() only accepts length <= 5 character");
        }
        db_insert('poll_choices')
            ->set_fv_a(['name' => $pollname,'choice_idx' => $i,'choice_text' => $v])
            ->execute();
    }
    sql_commit();
    run_hook('poll_registered',$pollname);
    return true;
}

function unregister_poll($pollname)
{
    sql_transaction();
    $container = db_query('poll_parameters')->get('container')->cond_fv('name',$pollname,'=')->execute_to_single();
    db_delete('poll_parameters')
        ->cond_fv('name',$pollname,'=')
        ->execute();
    db_delete('poll_choices')
        ->cond_fv('name',$pollname,'=')
        ->execute();
    db_delete('pollcont_' . $container)
        ->cond_fv('name',$pollname,'=')
        ->execute();
    sql_commit();
    run_hook('poll_unregistered',$pollname);
}

function get_poll_list($container = '',array $search_options = [])
{
    /* $search_options = [
                'activeonly' => true | false
                'fromdate' => '2019-01-20'
                'todate' => '2019-02-22'
                'add_voteactive' => true
                'add_votecount' => true //only works when container not empty
                'filled_by_uid' => UID   // need for filled_bysb_order
                'filled_to_ref' => REFID // need for filled_bysb_order
                'sort' => 'titletext'  '-name' or ['-voteactive','votecount','titletext']
            ]
    */

    $q = db_query('poll_parameters')
        ->get_a(['name','container','titletext','defidx','dstart','dend']);
    if($container != '')
        $q->cond_fv('container',$container,'=');
    if(isset($search_options['activeonly']) && $search_options['activeonly'])
    {
        $q->cond(cond('or')->fnull('dstart')->fe('dstart',sql_t('current_timestamp'),'<=',['efunction' => 'date']));
        $q->cond(cond('or')->fnull('dend')  ->fe('dend'  ,sql_t('current_timestamp'),'>=',['efunction' => 'date']));
    }

    if(isset($search_options['fromdate']) && $search_options['fromdate'] != '' && check_str($search_options['fromdate'],'isodate'))
        $q->cond(cond('or')->fnull('dstart')->fv('dstart',$search_options['fromdate'],'>=',['vfunction' => 'date']));
    if(isset($search_options['todate']) && $search_options['todate'] != '' && check_str($search_options['todate'],'isodate'))
        $q->cond(cond('or')->fnull('dend')  ->fv('dend',$search_options['todate'],'<=',['vfunction' => 'date']));

    if(isset($search_options['add_voteactive']) && $search_options['add_voteactive'])
    {
        $ct = sql_t('current_timestamp');
        $q->get("(SELECT (dstart IS NULL OR dstart <= date($ct)) AND
                         (dend IS NULL OR dend >= date($ct)))",'voteactive');
    }

    if(isset($search_options['add_votecount']) && $search_options['add_votecount'] &&
        check_str($search_options['filled_by_uid'],'number0') &&
        check_str($search_options['filled_to_ref'],'number0') &&
        check_str($container,'text0nsne'))
    {
        $uid = $search_options['filled_by_uid'];
        $ref = $search_options['filled_to_ref'];
        $q->get("(SELECT COUNT(ref)
                  FROM pollcont_$container AS pc
                  WHERE pc.name=poll_parameters.name AND
                        uid=$uid AND ref=$ref)",'votecount');
    }

    if(isset($search_options['sort']))
    {
        if(is_array($search_options['sort']) && count($search_options['sort']) > 0)
        {
            foreach($search_options['sort'] as $srt)
            {
                if(strlen($srt) > 1 && $srt[0] == '-')
                    $q->sort(substr($srt, 1), ['direction' => 'REVERSE']);
                else
                    $q->sort($srt);
            }
        }
        else
        {
            if($search_options['sort'] != '')
            {
                if(strlen($search_options['sort']) > 1 && $search_options['sort'][0] == '-')
                    $q->sort(substr($search_options['sort'], 1), ['direction' => 'REVERSE']);
                else
                    $q->sort($search_options['sort']);
            }
        }
    }

    $r = $q->execute_to_arrays();
    return $r;
}

function get_poll_parameters_by_pollname($pollname)
{
    $rp = db_query('poll_parameters')
        ->get_a(['container','titletext','defidx','dstart','dend'])
        ->cond_fv('name',$pollname,'=')
        ->execute_and_fetch();
    if(!isset($rp['container']))
        return null;
    return $rp;
}

function get_poll_parametervalue_by_pollname($pollname,$what)
{
    return db_query('poll_parameters')
        ->get($what)
        ->cond_fv('name',$pollname,'=')
        ->execute_to_single();
}

function get_poll_choices_array_by_pollname($pollname)
{
    $rc = db_query('poll_choices')
        ->get_a(['choice_idx','choice_text'])
        ->cond_fv('name',$pollname,'=')
        ->execute_to_arrays();

    $chs = [];
    foreach($rc as $rcc)
        $chs[$rcc['choice_idx']] = $rcc['choice_text'];
    return $chs;
}

function poll_is_user_voted($pollname,$container,$id,$uid)
{
    $cnt = db_query('pollcont_'.$container)
        ->counting()
        ->cond_fv('name',$pollname,'=')
        ->cond_fv('ref',$id,'=')
        ->cond_fv('uid',$uid,'=')
        ->execute_to_single();
    if($cnt > 0)
        return true;
    return false;
}

function poll_is_valid_by_date($date_start,$date_end)
{
    $currentdate = date('Y-m-d');
    if($date_start != '' && $currentdate < $date_start)
        return false;
    if($date_end   != '' && $currentdate > $date_end  )
        return false;
    return true;
}

function get_poll_results($container,$pollname,$id)
{
    $choices = get_poll_choices_array_by_pollname($pollname);
    $ns = sql_exec_fetchAll("SELECT COUNT(pid) as cnt,choice
                             FROM pollcont_$container
                             WHERE name=:pollname AND ref=:refid
                             GROUP BY choice",[':pollname' => $pollname,':refid' => $id]);
    $all = 0;
    foreach($ns as $nss)
        $all += $nss['cnt'];
    $results = [];
    foreach($choices as $idx => $text)
    {
        $cnt = 0;
        foreach($ns as $nss)
            if($nss['choice'] == $idx)
            {
                $cnt = $nss['cnt'];
                break;
            }
        $results[$text] = [
            'all' => $all,
            'count' => $cnt,
            'percent' => ($all == 0 ? 0 : intval(($cnt * 100) / $all)),
        ];
    }
    return $results;
}

function get_poll_block($pollname,$id,$maincssclass = '')
{
    global $user;
    global $site_config;

    if(!$user->auth)
        return '';

    $mcssclass = $site_config->activity_poll_main_css_class;
    if($maincssclass != '')
        $mcssclass = $maincssclass;

    $rp = get_poll_parameters_by_pollname($pollname);
    if($rp == null)
        return '';

    if(poll_access($pollname,$id,'view',$user) != ACTIVITYPOLL_ACCESS_ALLOW &&
       poll_access($pollname,$id,'add',$user) != ACTIVITYPOLL_ACCESS_ALLOW )
        return '';

    ob_start();
    print "<div class=\"$mcssclass\">";
    print '<div class="ckpoll_title">'.$rp['titletext'].'</div>';
    print '<div class="ckpoll_body_' . $pollname . '_' . $id . ' ckpoll_mbody">';
    print get_poll_block_inner($rp['container'],$pollname,$id,$rp['dstart'],$rp['dend']);
    print '</div>'; // .ckpoll_body
    print '</div>'; // .$mcssclass
    return ob_get_clean();
}

function get_poll_resultblock($results)
{
    global $site_config;
    return '<div class="ckpoll_result">' .
            call_user_func($site_config->activity_poll_showpoll_callback,$results) .
           '</div>';
}

function pollresult_generator_default($results)
{
    $t = new HtmlTable('poll_result_table');
    foreach($results as $text => $values)
    {
        $t->cell($text);
        $t->cell('<div class="ckpoll_innerbar" style="width: ' . $values['percent'] . '%;"></div>',
                ['class' => 'ckpoll_outbar']);
        $t->cell($values['percent'] . '% <small>(' . $values['count'] . ')</small>');
        $t->nrow();
    }
    return $t->get();
}

function pollresult_generator_horizontal($results)
{
    $t = new HtmlTable('poll_result_table');
    foreach($results as $text => $values)
        $t->cell($text,['type' => 'uni',"horizontal" => "center"]);
    $t->nrow();
    foreach($results as $text => $values)
        $t->cell('<div class="ckpoll_innerbar" style="height: '.$values['percent'].'%;"></div>',
            ['class' => 'ckpoll_outbar']);
    $t->nrow();
    foreach($results as $text => $values)
        $t->cell($values['percent'] . '%<br/> <small>(' . $values['count'] . ')</small>',
            ['type' => 'uni',"horizontal" => "center"]);
    return $t->get();
}

function get_poll_block_inner($container,$pollname,$id,$date_start = '',$date_end = '')
{
    global $user;

    if(poll_is_user_voted($pollname,$container,$id,$user->uid))
    {
        if(poll_access($pollname,$id,'view',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
            return '<div class="ckpoll_msg">' . t("You've already cast your vote.") . '</div>';

        $results = get_poll_results($container,$pollname,$id);
        return get_poll_resultblock($results);
    }

    if(!poll_is_valid_by_date($date_start,$date_end))
    {
        if(poll_access($pollname,$id,'view',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
            return '<div class="ckpoll_msg">' . t("The vote is not active.") . '</div>';

        $results = get_poll_results($container,$pollname,$id);
        return get_poll_resultblock($results);
    }

    if(poll_access($pollname,$id,'add',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
    {
        if(poll_access($pollname,$id,'view',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
            return '<div class="ckpoll_msg">' . t("You don't have the necessary permission to view this vote.") . '</div>';

        $results = get_poll_results($container,$pollname,$id);
        return get_poll_resultblock($results);
    }

    $f = new HtmlForm('form_poll_' . $pollname . '_' . $id);
    $f->action_ajax('votepollajax');

    $default = get_poll_parametervalue_by_pollname($pollname,'defidx');
    $choices = get_poll_choices_array_by_pollname($pollname);
    $name = 'poll_' . $pollname . '_' . $id;
    $f->select('radio',$name,$default,$choices,
        ['id' => $name .'_'. rand(100,999),
         'itemprefix' => '<div class="ckpoll_vitem">',
         'itemsuffix' => '</div>',
         'after' => '<div style="clear: both;"></div>',
        ]);

    $f->hidden('pollname',$pollname);
    $f->hidden('pollid',$id);
    $f->input('submit','Ok','Ok');
    return $f->get();
}

function aj_addvote_poll()
{
    global $user;
    if(!$user->auth)
        return '';

    par_def('pollname','text0nsne');
    par_def('pollid','number0');
    if(!par_ex('pollname') || !par_ex('pollid'))
        return;

    $pollname = par('pollname');
    $pollvarname = 'poll_'.$pollname.'_'.par('pollid');
    par_def($pollvarname,'text0nsne');

    if(poll_access($pollname,par('pollid'),'add',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
        return;

    $parameters = get_poll_parameters_by_pollname($pollname);
    if($parameters == null)
        return; //Cannot fetch the vote prameters
    $container = $parameters['container'];
    $currentdate = date('Y-m-d');
    if($parameters['dstart'] != '' && $currentdate < $parameters['dstart'])
    {
        ajax_add_alert(t('You can not vote, because the vote is not started yet!'));
        return;
    }
    if($parameters['dend'] != '' && $currentdate > $parameters['dend'])
    {
        ajax_add_alert(t('You can not vote, because the vote is already expired!'));
        return;
    }
    if(poll_is_user_voted($pollname,$container,par('pollid'),$user->uid))
    {
        run_hook('poll_already_vote');
        ajax_add_html('.ckpoll_body_' . $pollname . '_' . par('pollid'),
            get_poll_block_inner($container,$pollname,par('pollid')),$parameters['dstart'],$parameters['dend']);
        return; //Aready vote.
    }

    $choices = get_poll_choices_array_by_pollname($pollname);
    if(!array_key_exists(par($pollvarname),$choices))
        return;
    db_insert('pollcont_'.$container)
        ->set_fv_a([
            'name' => $pollname,
            'ref' => par('pollid'),
            'uid' => $user->uid,
            'choice' => par($pollvarname)])
        ->set_fe('created',sql_t('current_timestamp'))
        ->execute();
    run_hook('poll_vote',$pollname,par('pollid'),par($pollvarname));
    ajax_add_html('.ckpoll_body_' . $pollname . '_' . par('pollid'),
        get_poll_block_inner($container,$pollname,par('pollid')),$parameters['dstart'],$parameters['dend']);
}

function poll_access($pollname,$refid,$op,$account)
{
    if(!in_array($op,['view','add']))
        return ACTIVITYPOLL_ACCESS_DENY;
    $n = run_hook('poll_access',$pollname,$refid,$op,$account);

    if(in_array( ACTIVITYPOLL_ACCESS_DENY,$n))
        return ACTIVITYPOLL_ACCESS_DENY;
    if(in_array( ACTIVITYPOLL_ACCESS_ALLOW,$n))
        return ACTIVITYPOLL_ACCESS_ALLOW;

    //Default comment permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return ACTIVITYPOLL_ACCESS_ALLOW;
    return ACTIVITYPOLL_ACCESS_DENY;
}

function showpollpage_callback()
{
    global $site_config;
    global $user;

    par_def('pollname','text0nsne');
    par_def('id','number0');
    if(!par_ex('pollname') || !par_ex('id'))
        return '';
    if(count($site_config->poll_containers) == 0)
        return '';

    $pollname = par('pollname');
    $id = par('id');

    $rp = get_poll_parameters_by_pollname($pollname);
    if($rp == null)
        return '';
    if(poll_access($pollname,$id,'view',$user) != ACTIVITYPOLL_ACCESS_ALLOW)
        return '';

    $container = get_poll_parametervalue_by_pollname($pollname,'container');
    $results = get_poll_results($container,$pollname,$id);

    ob_start();
    print '<div class="'.$site_config->activity_poll_main_css_class.'">';
    print '<div class="ckpoll_title">'.$rp['titletext'].'</div>';
    print '<div class="ckpoll_body_' . $pollname . '_' . $id . ' ckpoll_mbody">';
    print get_poll_resultblock($results);
    print '</div>';
    return ob_get_clean();
}

function hook_activitypoll_required_sql_schema()
{
    global $site_config;
    $t = [];
    $poll_active = false;
    foreach($site_config->poll_containers as $cnt)
    {
        $t["activitypoll_module_poll_table_$cnt"] =
            [
                "tablename" => "pollcont_$cnt",
                "columns" => [
                    'pid'     => 'SERIAL',
                    'name'    => 'VARCHAR(20)',
                    'ref'     => 'BIGINT',
                    'uid'     => 'BIGINT',
                    'created' => 'TIMESTAMP',
                    'choice'  => 'VARCHAR(5)',
                ],
            ];
        $poll_active = true;
    }
    if($poll_active)
    {
        $t["activitypoll_module_pollparameters_table"] =
            [
                "tablename" => "poll_parameters",
                "columns" => [
                    'name'      => 'VARCHAR(20) UNIQUE',
                    'container' => 'VARCHAR(128)',
                    'titletext' => sql_t('longtext_type'),
                    'defidx'    => 'VARCHAR(5)',
                    'dstart'    => 'DATE DEFAULT NULL',
                    'dend'      => 'DATE DEFAULT NULL',
                ],
            ];
        $t["activitypoll_module_pollchoices_table"] =
            [
                "tablename" => "poll_choices",
                "columns" => [
                    'name'        => 'VARCHAR(20)',
                    'choice_idx'  => 'VARCHAR(5)',
                    'choice_text' => sql_t('longtext_type'),
                ],
            ];
    }
    return $t;
}


function hook_activitypoll_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = [
            'activitypoll' => [
                'path' => codkep_get_path('activitypoll','server') . '/activitypoll.mdoc',
                'index' => false ,
                'imagepath' => codkep_get_path('activitypoll','web') .'/docimages'
            ]
        ];
    }
    return $docs;
}

//end.
