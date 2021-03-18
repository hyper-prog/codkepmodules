<?php
/* Cron module fro CodKep
 *
 *  Module name: cron
 *  Dependencies: sql, user
 *
 *  Written by Péter Deák (C) hyper80@gmail.com , License GPLv2
 */

function hook_cron_boot()
{
    global $codkep_cron;
    $codkep_cron = new stdClass();
    $codkep_cron->cron_key = '';

    $codkep_cron->define_routes = true;
    $codkep_cron->block_unauthorized_requests = true;
    $codkep_cron->url = "cron";
    $codkep_cron->lastrun_sqltable = 'cron_lastrun';
    $codkep_cron->debug_to_log = false;
    $codkep_cron->runinfo_to_log = true;
}

function hook_cron_defineroute()
{
    global $codkep_cron;
    if($codkep_cron->define_routes)
        return [
                ["path" => $codkep_cron->url,
                 "callback" => "cronmodule_cron_callback",
                 "theme" => "base_page",],
               ];
    return [];
}

function cronmodule_cron_callback()
{
    global $codkep_cron;

    userblocking_check();
    par_def('cron_key','text0nsne');
    $cron_key = par('cron_key');
    if($codkep_cron->cron_key != $cron_key || $codkep_cron->cron_key == '')
    {
        if($codkep_cron->block_unauthorized_requests)
            userblocking_set('Cron module - cron_key error');
        return '';
    }
    cronmodule_tick();
    return 'Ok';
}

function cronmodule_tick()
{
    global $codkep_cron;
    $crons = run_hook('cron');

    $now = new DateTime();
    $now->setTimestamp(time());

    foreach($crons as $ci)
    {
        if(!isset($ci['callback']))
            continue;
        $callback = $ci['callback'];

        if($codkep_cron->debug_to_log)
        {
            d1('--Corn item --------------------------------');
            d1('Callback: ' . $callback);
            d1('Current   : ' . $now->format('Y-m-d H:i:s'));
        }
        $r = db_query($codkep_cron->lastrun_sqltable)
                ->get('lastrun')
                ->cond_fv('callback',$callback,"=")
                ->execute_and_fetch();

        $lastrun = NULL;
        $has_record = false;
        $should_run = false;
        if(isset($r['lastrun']) && $r['lastrun'] != '')
        {
            $has_record = true;
            $lastrun = new DateTime();
            $lastrun->setTimestamp($r['lastrun']);
            if($codkep_cron->debug_to_log)
                d1('Last run  : '.$lastrun->format('Y-m-d H:i:s'));

            $interval_end = $lastrun;
            if(isset($ci['interval_minute']))
                $interval_end->add(new DateInterval('PT'.intval($ci['interval_minute']).'M'));
            if(isset($ci['interval_hour']))
                $interval_end->add(new DateInterval('PT'.intval($ci['interval_hour']).'H'));
            if(isset($ci['interval_day']))
                $interval_end->add(new DateInterval('P'.intval($ci['interval_day']).'D'));
            if(isset($ci['interval_month']))
                $interval_end->add(new DateInterval('P'.intval($ci['interval_month']).'M'));
            if($codkep_cron->debug_to_log)
                d1('Next after: '.$interval_end->format('Y-m-d H:i:s'));

            if($now >= $interval_end)
                $should_run = true;
        }

        if(!$has_record)
        {
            if($codkep_cron->debug_to_log)
                d1('Never run before: triggering run');
            $should_run = true;
        }

        if(!$should_run)
            continue;

        if(isset($ci['waituntil_minute']) && intval($now->format('i')) < intval($ci['waituntil_minute']))
                $should_run = false;
        if(isset($ci['waituntil_hour']) &&   intval($now->format('G')) < intval($ci['waituntil_hour']))
                $should_run = false;
        if(isset($ci['waituntil_dayofweek']) && intval($now->format('w')) < intval($ci['waituntil_dayofweek']))
                $should_run = false;
        if(isset($ci['waituntil_dayofmonth']) && intval($now->format('j')) < intval($ci['waituntil_dayofmonth']))
                $should_run = false;
        if(isset($ci['waituntil_monthofyear']) && intval($now->format('n')) < intval($ci['waituntil_monthofyear']))
                $should_run = false;
        if($codkep_cron->debug_to_log && !$should_run)
            d1('Skip running until the desired time');

        if($should_run)
        {
            if($codkep_cron->runinfo_to_log)
                d1('Codkep cron module - Executing: '.$callback);
            if($has_record)
                db_update($codkep_cron->lastrun_sqltable)
                    ->set_fv('lastrun',time())
                    ->cond_fv('callback',$callback,"=")
                    ->execute();
            if(!$has_record)
                db_insert($codkep_cron->lastrun_sqltable)
                    ->set_fe('lastrun',time())
                    ->set_fv('callback',$callback)
                    ->execute();

            if(is_callable($callback))
                call_user_func($callback);
        }
    }
}

function hook_cron_required_sql_schema()
{
    global $codkep_cron;
    $t = [];

    $t['cron_module_lastrun_table'] =
        [
            "tablename" => $codkep_cron->lastrun_sqltable,
            "columns" => [
                'callback'  => 'VARCHAR(128)',
                'lastrun'   => 'BIGINT',
            ],
        ];
    return $t;
}

function hook_cron_introducer()
{
    $html = '';

    $crons = run_hook('cron');
    $html .= 'Scheduled tasks: ';
    $n = 0;
    foreach($crons as $ci)
    {
        if (!isset($ci['callback']))
            continue;
        $callback = $ci['callback'];
        if($n > 0)
            $html .= ',';
        $html .= '<code>'.$callback.'()<code>';
        $n++;
    }
    return ['Cron' => $html];
}

function hook_cron_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = [
            'cron' => [
                'path' => codkep_get_path('cron','server') . '/cron.mdoc',
                'index' => false ,
                'imagepath' => codkep_get_path('cron','web') .'/docimages'
            ]
        ];
    }
    return $docs;
}
