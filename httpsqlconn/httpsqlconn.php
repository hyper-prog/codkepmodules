<?php
/* JSON encapsulated SQL command receiver module for C++ HSqlBuilder class in gSAFE
 *
 *  Module name: httpsqlconn
 *  Dependencies: sql,user
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_httpsqlconn_boot()
{
    global $httpsqlconn;
    $httpsqlconn = new stdClass();

    $httpsqlconn->define_routes = false;
    $httpsqlconn->resources = [];
    $httpsqlconn->condition_templates = [];
    $httpsqlconn->input_encoder = NULL;
    $httpsqlconn->output_encoder = NULL;
}

function hook_httpsqlconn_defineroute()
{
    global $httpsqlconn;
    if($httpsqlconn->define_routes)
        return [
                ["path" => "httpsqlconn/{resourcename}/{fastid}",
                 "callback" => "httpsqlconn_connection_callback","type" => "json"],
               ];
    return [];
}

function httpsqlconn_connection_callback()
{
    global $httpsqlconn;

    par_def("resourcename","text0nsne");
    par_def("fastid","text0nsne");

    if(userblocking_check())
    {
        d1('HttpSqlConn - blocked client: '.get_remote_address());
        return NULL;
    }

    $resource = par("resourcename");
    if(!isset($httpsqlconn->resources[$resource]))
    {
        d1('HttpSqlConn - unknown resource req from client: '.get_remote_address());
        userblocking_set("HttpSqlConn: Unknown resource (PR1)");
        return NULL;
    }
    if(!isset($httpsqlconn->resources[$resource]['fastid']) ||
        $httpsqlconn->resources[$resource]['fastid'] != par('fastid'))
    {
        d1('HttpSqlConn - fast-id not valid from client: '.get_remote_address());
        userblocking_set("HttpSqlConn: FastId error (PR2)");
        return NULL;
    }

    $data = file_get_contents('php://input');

    $encdata = $data;
    if($httpsqlconn->input_encoder != NULL && is_callable($httpsqlconn->input_encoder))
        $encdata = call_user_func($httpsqlconn->input_encoder,$data,$resource);

    if($encdata == "")
        return NULL;

    userblocking_clear();

    if(isset($httpsqlconn->resources[$resource]['sqlreconnect']) &&
       $httpsqlconn->resources[$resource]['sqlreconnect'] )
    {
        //Reconnect with special user
        global $user_module_settings;
        $user_module_settings->disable_after_deliver_garbage_collection = true;
        sql_disconnect();
        global $db;
        $db->user       = $httpsqlconn->resources[$resource]['sql_user'];
        $db->password   = $httpsqlconn->resources[$resource]['sql_password'];
        sql_connect();
        if(!$db->open)
        {
            d1('HttpSqlConn: Cannot reopen database with the special user.');
            return NULL;
        }
    }

    $resCallback = 'httpsqlconnProvider';
    if(isset($httpsqlconn->resources[$resource]['dataProvider']))
        $resCallback = $httpsqlconn->resources[$resource]['dataProvider'];

    $r = call_user_func($resCallback,$resource,$encdata);

    $renc = $r;
    if($httpsqlconn->output_encoder != NULL && is_callable($httpsqlconn->output_encoder))
        $renc = call_user_func($httpsqlconn->output_encoder,$r,$resource);

    return $renc;
}

// ///////////////////////////////////////////////////////////////////////////////////////// //
// Data provider functions
// ///////////////////////////////////////////////////////////////////////////////////////// //

function httpsqlconn_command_enabled($resource,$command)
{
    $n = run_hook('httpsqlconn_command_enabled',$resource,$command);
    if(in_array(NODE_ACCESS_DENY,$n))
        return NODE_ACCESS_DENY;
    if(in_array(NODE_ACCESS_ALLOW,$n))
        return NODE_ACCESS_ALLOW;

    //Default permissions:
    return NODE_ACCESS_DENY;
}

function httpsqlconn_operation_enabled($resource,$operation,$tablename)
{
    if(!in_array($operation,['select','update','insert','delete']))
        return NODE_ACCESS_DENY;

    $n = run_hook('httpsqlconn_operation_enabled',$resource,$operation,$tablename);
    if(in_array(NODE_ACCESS_DENY,$n))
        return NODE_ACCESS_DENY;
    if(in_array(NODE_ACCESS_ALLOW,$n))
        return NODE_ACCESS_ALLOW;

    //Default permissions:
    return NODE_ACCESS_DENY;
}

function tableNameFromHttpSqlConnString($resource,$operation,$tablename)
{
    if($tablename == "" || $tablename === NULL || !check_str($tablename,"text1ns"))
        throw new Exception("HttpSqlConn error: Invalid table name.");
    if(httpsqlconn_operation_enabled($resource,$operation,$tablename) != NODE_ACCESS_ALLOW)
        throw new Exception("HttpSqlConn error: Not enabled \"${operation}\" ".
                            "operation on table \"${tablename}\" in resource \"${resource}\".");
    return $tablename;
}

function tableNameFromHttpSqlConnArray($resource,$jsonarray,$operation)
{
    if(!isset($jsonarray['tablename']))
        throw new Exception("HttpSqlConn error: Json structure error, cannot find tablename.");
    return tableNameFromHttpSqlConnString($resource,$operation,$jsonarray['tablename']);
}

function fieldsFromHttpSqlConnArray($jsonarray)
{
    if(!isset($jsonarray['fields']))
        throw new Exception("HttpSqlConn error: Json structure error, cannot find fields.");
    $f = $jsonarray['fields'];
    if(!is_array($f))
        throw new Exception("HttpSqlConn error: Json structure error, field section error.");
    foreach($f as $ff)
        if(!check_str($ff,'neuttext'))
            throw new Exception("Invalid field name.");
    return $f;
}

function httpsqlconnProvider($resource,$parameters)
{
    $jsonarray = json_decode($parameters,true);
    if(!isset($jsonarray['reqId']))
    {
        d1('HttpSqlConn provider: Json decode error. (AJD0)');
        return ['status' => 'ERROR','return' => '','array' => []];
    }

    $reqId = $jsonarray['reqId'];
    if(check_str($reqId,'text1ns') && is_callable('dc_executor__'.$reqId))
    {
        if(httpsqlconn_command_enabled($resource,$reqId) != NODE_ACCESS_ALLOW)
        {
            d1("HttpSqlConn error: Not enabled command $reqId in resource $resource.");
            return ['status' => 'ERROR','return' => '','array' => []];
        }

        try
        {
            return call_user_func('dc_executor__' . $reqId, $resource, $jsonarray);
        }
        catch(Exception $e)
        {
            d1("HttpSqlConn processing error: ".$e->getMessage());
            return ['status' => 'ERROR','return' => '','array' => []];
        }

    }
    d1("HttpSqlConn error: Unknown or not valid command in resource $resource:".$reqId);
    return ['status' => 'ERROR','return' => '','array' => []];
}

function dc_executor__req_ping($res,$jsonarray)
{
    return ['status' => 'Ok','return' => "Pong",'array' => []];
}

function dc_executor__req_server_time($res,$jsonarray)
{
    $st = sql_exec_single("SELECT " . sql_t('current_timestamp'));
    return ['status' => 'Ok','return' => $st,'array' => []];
}

function dc_executor__check_fields_exists($res,$jsonarray)
{
    global $db;
    $t = tableNameFromHttpSqlConnArray($res,$jsonarray,"select");
    $fs = fieldsFromHttpSqlConnArray($jsonarray);
    foreach($fs as $f)
    {
        $sql = "SELECT $f FROM $t LIMIT 1";
        sql_exec_noredirect($sql);
        if($db->error)
        {
            d1('HttpSqlConn sql error(FE1):'.$db->errormsg);
            return ['status' => 'ERROR', 'return' => '','array' => []];
        }
    }
    return ['status' => 'Ok','return' => 'all_exists','array' => []];
}

function dc_executor__query_uni($res,$jsonarray)
{
    $qspec = $jsonarray['query_spec'];

    $action = "anything";
    if($qspec['type'] == 'select')
        $action = "select";
    if($qspec['type'] == 'insert')
        $action = "insert";
    if($qspec['type'] == 'update')
        $action = "update";
    if($qspec['type'] == 'delete')
        $action = "delete";

    $returnType = $qspec['return'];
    $base_table = tableNameFromHttpSqlConnString($res,$action,$qspec['table_name']);
    $base_alias = isset($qspec['table_alias']) ? $qspec['table_alias'] : "";

    $fields = isset($qspec['fields'])     ? $qspec['fields']     : [];
    $joins  = isset($qspec['joins'])      ? $qspec['joins']      : [];
    $conds  = isset($qspec['conditions']) ? $qspec['conditions'] : [];
    $sorts  = isset($qspec['sort'])       ? $qspec['sort']       : [];

    $q = db_action(($action == 'select' ? 'query' : $action),$base_table,$base_alias);

    executorQueryUni_fieldsSet($res,$action,$fields,$q);
    executorQueryUni_fieldsGet($res,$action,$fields,$q);
    executorQueryUni_joins($res,$action,$joins,$q);
    executorQueryUni_condtop($res,$action,$conds,$q,"db");
    executorQueryUni_sort($res,$action,$sorts,$q);

    if($action == 'select')
    {
        $limit = $qspec['limit'];
        if($limit != "" && $limit > 0)
            $q->length($limit);

        $countfield = $qspec['countfield'];
        $counttable = $qspec['counttable'];
        $countalias = $qspec['countalias'];
        if($countfield != "")
        {
            if (!check_str($countfield, 'text1ns'))
                throw new Exception("Invalid counting field content. (ICC1)");

            $cf = $countfield;
            if($counttable != "")
            {
                if(!check_str($counttable, 'text1ns'))
                    throw new Exception("Invalid counting field content. (ICC2)");
                $cf = [$counttable, $countfield];
            }
            $q->counting($cf, $countalias);
        }
    }

    global $db;
    $r_table = [];
    $r_single = "";

    if($returnType == "dryrun")
        return ['status' => 'Ok','array' => [],'return' => $q->local_cmd()];

    if($action == 'select')
    {
        if($returnType == 'table')
        {
            $r_single = 'array';
            $r_table = $q->execute_to_arrays(["noredirect" => true,"fetch_names_only" => true]);
        }
        if($returnType == 'single')
        {
            $do = $q->execute(["noredirect" => true]);
            if($do != NULL)
            {
                $row = $do->fetch();
                if(isset($row[0]))
                    $r_single = $row[0];
            }
        }
        if($returnType == 'noreturn')
        {
            $q->execute(["noredirect" => true]);
        }
    }
    else
        $q->execute(["noredirect" => true]);

    if($db->error)
    {
        d1('HttpSqlConn sql error(UQ1):'.$db->errormsg);
        return ['status' => 'ERROR', 'return' => '','array' => []];
    }
    return ['status' => 'Ok','array' => $r_table,'return' => $r_single];
}

function executorQueryUni_fieldsGet($res,$action,$fields,$q)
{
    if($action != 'select')
        return;

    foreach($fields as $f)
    {
        if(!isset($f['name']))
            continue;

        $opt = [];
        $optsa = explode(';',$f['options']);
        foreach($optsa as $opts)
        {
            if(substr($opts,0,9) == 'function=')
                $opt['function'] = substr($opts,9);
            if(substr($opts,0,10) == 'more_args=')
                $opt['more_args'] = substr($opts,10);
        }
        $fspec = $f['name'];
        if(!check_str($fspec,'text1ns'))
            throw new Exception("Invalid field content. (IF1)");
        if($f['table'] != "")
        {
            if(!check_str($f['table'],'text1ns'))
                throw new Exception("Invalid field content. (IF2)");
            $fspec = [$f['table'], $f['name']];
        }
        $q->get($fspec,$f['alias'],$opt);
    }
}

function executorQueryUni_fieldsSet($res,$action,$fields,$q)
{
    if($action != 'insert' && $action != "update")
        return;

    foreach($fields as $f)
    {
        if(!isset($f['name']))
            continue;

        $opt = [];
        $optsa = explode(';',$f['options']);
        foreach($optsa as $opts)
        {
            if(substr($opts,0,9) == 'function=')
                $opt['function'] = substr($opts,9);
            if(substr($opts,0,10) == 'more_args=')
                $opt['more_args'] = substr($opts,10);
        }
        $name = $f['name'];
        $value = $f['value'];
        $type  = $f['type'];

        if(!check_str($name,'text1ns'))
            throw new Exception("Invalid field content. (SF1)");

        if($type == "set_val")
            $q->set_fv($name,$value,$opt);
        if($type == "set_expr")
            $q->set_fe($name,$value,$opt);
    }
}

function executorQueryUni_joins($res,$action,$joins,$q)
{
    if($action != 'select')
        return;

    foreach($joins as $j)
    {
        if(!isset($j['jointable']))
            continue;

        $jt = tableNameFromHttpSqlConnString($res,$action,$j['jointable']);

        if(!isset($j['condtoprel']))
            throw new Exception("Invalid join condition specification. (IJCN0)");
        $joinCondTopRel = $j['condtoprel'];
        if($joinCondTopRel != 'and' && $joinCondTopRel != 'or')
            throw new Exception("Invalid join condition specification. (IJCN1)");
        if(!isset($j['cond']) && !is_array($j['cond']))
            throw new Exception("Invalid join condition specification. (IJCN2)");
        $joinCondTopArray = $j['cond'];

        $jcond = cond($joinCondTopRel);
        executorQueryUni_condtop($res,$action,$joinCondTopArray,$jcond,"cond");
        if($j['type'] == 'inner')
            $q->join($jt, $j['jointablealias'], $jcond);

        if($j['type'] == 'leftouter')
            $q->join_opt($jt, $j['jointablealias'], $jcond);
    }
}

function executorQueryUni_condtop($res,$action,$condarray,$q,$objtype)
{
    if($action != 'select' && $action != "update" && $action != "delete")
        return;

    foreach($condarray as $cond)
    {
        if(!isset($cond['type']))
            throw new Exception("Invalid condition specification. (ICNT0)");

        $ctype = $cond['type'];
        if($ctype == "sub")
        {
            $rel = $cond['relation'];
            if($rel != "and" && $rel != "or")
                throw new Exception("Invalid sub condition relation. (ICNR0)");
            if(!isset($cond['subcond']) || !is_array($cond['subcond']))
                throw new Exception("Invalid sub condition specification. (ICNS0)");

            $qsub = cond($rel);
            foreach($cond['subcond'] as $index => $subcond)
                executorQueryUni_cond($res, $action, $subcond, $qsub);
            $q->cond($qsub);
        }
        else
        {
            executorQueryUni_cond_simple($res, $action, $cond, $q, $objtype);
        }
    }
}

function executorQueryUni_cond($res,$action,$cond,$qc)
{
    if($action != 'select' && $action != "update" && $action != "delete")
        return;

    if(!isset($cond['type']))
        throw new Exception("Invalid condition specification. (ICNT0)");

    $ctype = $cond['type'];
    if($ctype == "sub")
    {
        $rel = $cond['relation'];
        if($rel != "and" && $rel != "or")
            throw new Exception("Invalid sub condition relation. (ICNR0)");
        if(!isset($cond['subcond']) || !is_array($cond['subcond']))
            throw new Exception("Invalid sub condition specification. (ICNS0)");

        $qsub = cond($rel);
        foreach($cond['subcond'] as $index => $subcond)
            executorQueryUni_cond($res, $action, $subcond, $qsub);
        if($qc == NULL)
            return $qsub;
        $qc->cond($qsub);
        return;
    }
    else
    {
        executorQueryUni_cond_simple($res, $action, $cond, $qc, "cond");
    }
}

function executorQueryUni_cond_simple($res,$action,$cond,$obj,$objtype)
{
    if(!isset($cond['type']))
        throw new Exception("Invalid condition specification. (ICNTS0)");

    $ctype = $cond['type'];

    $f1 = $cond['field1'];
    $f2 = $cond['field2'];

    if(!check_str($f1, 'text1ns'))
        throw new Exception("Invalid condition field content. (ICN1)");
    if(!check_str($f2, 'text1ns'))
        throw new Exception("Invalid connection field content. (ICN2)");

    if($cond['table1'] != "")
    {
        if(!check_str($cond['table1'], 'text1ns'))
            throw new Exception("Invalid condition field content. (ICN3)");
        $f1 = [$cond['table1'], $cond['field1']];
    }

    if($cond['table2'] != "")
    {
        if(!check_str($cond['table2'], 'text1ns'))
            throw new Exception("Invalid condition field content. (ICN4)");
        $f2 = [$cond['table2'], $cond['field2']];
    }

    $op = isset($cond['op']) ? $cond['op'] : "";
    $ev = $cond['value'];
    $vt = $cond['valtype'];

    $opt = [];
    $optsa = explode(';', $cond['options']);
    foreach($optsa as $opts)
    {
        $subopts = explode("=", $opts);
        if(count($subopts) == 2)
            $opt[$subopts[0]] = $subopts[1];
    }

    if(!in_array($op, ['', '=', '!=', '>', '<', '>=', '<=', 'regex']))
        throw new Exception("Operation not supported.");


    if($ctype == 'ff')
    {
        if($objtype == "db")
            $obj->cond_ff($f1, $f2, $op, $opt);
        if($objtype == "cond")
            $obj->ff($f1, $f2, $op, $opt);
    }
    if($ctype == 'fv')
    {
        if($objtype == "db")
            $obj->cond_fv($f1, $ev, $op, $opt);
        if($objtype == "cond")
            $obj->fv($f1, $ev, $op, $opt);
    }
    if($ctype == 'fe')
    {
        if($objtype == "db")
            $obj->cond_fe($f1, $ev, $op, $opt);
        if($objtype == "cond")
            $obj->fe($f1, $ev, $op, $opt);
    }
    if($ctype == 'fb')
    {
        if($objtype == "db")
            $obj->cond_fb($f1, $opt);
        if($objtype == "cond")
            $obj->fb($f1, $opt);
    }

    if($ctype == 'spec_f' || $ctype == 'spec_v' || $ctype == 'spec_vf')
    {
        global $httpsqlconn;
        $templ_name = $cond['template'];

        if(!check_str($ev, 'text3'))
            throw new Exception("Invalid condition field content. (ICSV1)");

        if(!isset($httpsqlconn->condition_templates[$templ_name]))
            throw new Exception("Unsupported condition template name.");
        $sqlcond = $httpsqlconn->condition_templates[$templ_name];

        $f = $cond['table1'] == "" ? $cond['field1'] : $cond['table1'] . "." . $cond['field1'];
        $insv = $ev;
        if($vt == "quoted")
            $insv = "'$ev'";

        if(isset($opt['ffunction']) && $opt['ffunction'] != "")
            $f = $opt['ffunction'] . "(" . $f . ")";

        if(isset($opt['vfunction']) && $opt['vfunction'] != "")
            $insv = $opt['vfunction'] . "(" . $insv . ")";

        $sqlcond = str_replace("__FIELD__", $f, $sqlcond);
        $sqlcond = str_replace("__VALUE__", $insv, $sqlcond);

        if($objtype == "db")
            $obj->cond_sql($sqlcond);
        if($objtype == "cond")
            $obj->sql($sqlcond);
    }
}

function executorQueryUni_sort($res,$action,$sorts,$q)
{
    if($action != 'select')
        return;

    foreach($sorts as $s)
    {
        if(!isset($s['field']))
            continue;

        $f1 = $s['field'];
        if(!check_str($f1,'text1ns'))
            throw new Exception("Invalid sort field content. (IS1)");
        if($s['table'] != "")
        {
            if(!check_str($s['table'],'text1ns'))
                throw new Exception("Invalid sort field content. (IS2)");
            $f1 = [$s['table'], $s['field']];
        }
        $opt = [];
        $optsa = explode(';',$s['options']);
        foreach($optsa as $opts)
        {
            $subopts = explode("=",$opts);
            if(count($subopts) == 2)
                $opt[$subopts[0]] = $subopts[1];
        }
        $q->sort($f1,$opt);
    }
}


function hook_httpsqlconn_introducer()
{
    $html = '';
    global $httpsqlconn;

    if($httpsqlconn->define_routes)
        $html .= 'HttpSqlConn url: <code>'.$_SERVER['REQUEST_SCHEME'].'://httpsqlconn/{resourcename}/{fastid}</code><br/>';
    $html .= 'Available resources: ';
    $n = 0;
    foreach($httpsqlconn->resources as $rname => $rdata)
    {
        $html .= ($n > 0 ? ', ': '') . '<code>'.$rname.'</code>';
        ++$n;
    }
    $html .= '<br/>';
    return ['HttpSqlConn' => $html];
}

function hook_httpsqlconn_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = [
            'httpsqlconn' => [
                'path' => codkep_get_path('httpsqlconn','server') . '/httpsqlconn.mdoc',
                'index' => false ,
                'imagepath' => codkep_get_path('httpsqlconn','web') .'/docimages'
            ]
        ];
    }
    return $docs;
}
