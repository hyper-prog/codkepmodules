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
        return ['status' => 'ERROR','rtype' => 'single','return' => '','array' => []];
    }

    $uinit_data = [];
    $ureturn = ['status' => '','rtype' => 'single','return' => '','array' => []];

    if(isset($jsonarray['session']))
    {
        if(isset($jsonarray['session']['apitoken']) && isset($jsonarray['session']['chkval']))
        {
            $apitoken = $jsonarray['session']['apitoken'];
            $uinit_data = user_init_api($apitoken,$jsonarray['session']['chkval']);

            global $user;
            if($user->auth)
            {
                $ureturn['session'] = [
                    'auth' => 'yes',
                    'login' => $user->login,
                    ];

                if(isset($uinit_data['action']) && $uinit_data['action'] == 'keychange')
                {
                    $ureturn['session']['keychange'] = 'required';
                    $ureturn['session']['chkval'] = $uinit_data['chkval'];
                }
            }
        }
    }

    $reqId = $jsonarray['reqId'];
    if(check_str($reqId,'text1ns') && is_callable('dc_executor__'.$reqId))
    {
        if(httpsqlconn_command_enabled($resource,$reqId) != NODE_ACCESS_ALLOW)
        {
            d1("HttpSqlConn error: Not enabled command $reqId in resource $resource.");
            $ureturn['status'] = 'ERROR';
            return $ureturn;
        }

        try
        {
            $urr = call_user_func('dc_executor__' . $reqId, $resource, $jsonarray);

            if(isset($urr['status']))
                $ureturn['status'] = $urr['status'];
            else
                $ureturn['status'] = 'ERROR';

            if(isset($urr['rtype']))
                $ureturn['rtype']  = $urr['rtype'];
            if(isset($urr['return']))
                $ureturn['return'] = $urr['return'];
            if(isset($urr['array']))
                $ureturn['array']  = $urr['array'];

            return $ureturn;
        }
        catch(Exception $e)
        {
            d1("HttpSqlConn processing error: ".$e->getMessage());
            $ureturn['status'] = 'ERROR';
            $ureturn['rtype']  = 'single';
            $ureturn['return'] = '';
            $ureturn['array']  = [];
            return $ureturn;
        }
    }
    d1("HttpSqlConn error: Unknown or not valid command in resource $resource:".$reqId);
    $ureturn['status'] = 'ERROR';
    $ureturn['rtype']  = 'single';
    $ureturn['return'] = '';
    $ureturn['array']  = [];
    return $ureturn;
}

function dc_executor__req_ping($res,$jsonarray)
{
    return ['status' => 'Ok','rtype' => 'single','return' => "Pong"];
}

function dc_executor__req_server_time($res,$jsonarray)
{
    $st = sql_exec_single("SELECT " . sql_t('current_timestamp'));
    return ['status' => 'Ok','rtype' => 'single','return' => $st];
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
            return ['status' => 'ERROR', 'return' => ''];
        }
    }
    return ['status' => 'Ok','rtype' => 'single','return' => 'all_exists'];
}

function dc_executor__login_user($res,$jsonarray)
{
    if(!isset($jsonarray["parameters"]["login"]) || $jsonarray["parameters"]["login"] == "")
        return ['status' => 'ERROR','return' => 'error-spu01'];
    if(!isset($jsonarray["parameters"]["cred"]) || $jsonarray["parameters"]["cred"] == "")
        return ['status' => 'ERROR','return' => 'error-spu02'];

    $back = user_login_api($jsonarray["parameters"]["login"],$jsonarray["parameters"]["cred"]);

    global $user;
    if(!$user->auth || $back['authstatus'] != 'success')
        return ['status' => 'ERROR','return' => 'error-spu06'];

    return ['status' => 'Ok','return' => 'array','array' => [
               'login'    => $user->login,
               'name'     => $user->name,
               'apitoken' => $back['apitoken'],
               'chkval'   => $back['chkval'],
               ]];
}

function dc_executor__whoami_user($res,$jsonarray)
{
    global $user;
    return ['status' => 'Ok','return' => 'array','array' => [
               'auth'     => $user->auth ? "yes" : "no",
               'login'    => $user->login,
               'name'     => $user->name,
               ]];
}

function dc_executor__logout_user($res,$jsonarray)
{
    global $user;

    if($user->auth && $user->client == 'api')
        user_logout_api($user->apitoken);

    return ['status' => 'Ok','return' => ''];
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
        return ['status' => 'Ok','rtype' => 'single','array' => [],'return' => $q->local_cmd()];

    $r_rtype = 'unknown';
    if($action == 'select')
    {
        if($returnType == 'table')
        {
            $r_single = 'array';
            $r_table = $q->execute_to_arrays(["noredirect" => true,"fetch_names_only" => true]);
            $r_rtype = 'array';
        }
        if($returnType == 'single')
        {
            $r_rtype = 'single';
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
            $r_rtype = 'none';
            $q->execute(["noredirect" => true]);
        }
    }
    else
    {
        $r_rtype = 'none';
        $q->execute(["noredirect" => true]);
    }

    if($db->error)
    {
        d1('HttpSqlConn sql error(UQ1):'.$db->errormsg);
        return ['status' => 'ERROR', 'return' => '','array' => []];
    }
    return ['status' => 'Ok','rtype' => $r_rtype,'array' => $r_table,'return' => $r_single];
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
        {
            if(in_array("dialected_element=yes",$optsa))
                $value = sql_t(str_replace("<<<","",str_replace(">>>","",$value)));
            $q->set_fe($name,$value,$opt);
        }
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

function definition_converter_codkep_to_gsafe2($def)
{
    $r = [];

    $r['name'] = $def['table'];
    $r['title'] = $def['name'];

    $fields = [];
    foreach($def['fields'] as $idx => $f)
    {
        $nf = [];
        $atts = [];

        $nf['type'] = '';

        if($f['type'] == 'keys')
            $nf['type'] = 'skey';

        if($f['type'] == 'keyn')
            $nf['type'] = 'nkey';

        if($f['type'] == 'smalltext')
            $nf['type'] = 'smalltext';

        if($f['type'] == 'largetext')
        {
            $nf['type'] = 'largetext';
            if(isset($f['row']))
                $nf['char_row'] = $f['row'];
            if(isset($f['col']))
                $nf['char_col'] = $f['col'];
        }

        if($f['type'] == 'txtselect')
            $nf['type'] = 'txtselect';

        if($f['type'] == 'numselect')
            $nf['type'] = 'numselect';

        if($f['type'] == 'txtselect_intrange')
        {
            $nf['type'] = 'txtselect';
            $atts["autofill_selectables_start"] = strval($f['start']);
            $atts["autofill_selectables_end"] = strval($f['end']);
        }

        if($f['type'] == 'numselect_intrange')
        {
            $nf['type'] = 'numselect';
            $atts["autofill_selectables_start"] = strval($f['start']);
            $atts["autofill_selectables_end"] = strval($f['end']);
        }

        if($f['type'] == 'txtradio')
        {
            $nf['type'] = 'txtselect';
            $atts["radiobuttons"] = "yes";
        }

        if($f['type'] == 'check')
            $nf['type'] = 'check';

        if($f['type'] == 'number')
            $nf['type'] = 'number';

        if($f['type'] == 'float')
            $nf['type'] = 'floating';

        if($f['type'] == 'static')
            $nf['type'] = 'static';

        if($f['type'] == 'numradio')
        {
            $nf['type'] = 'numselect';
            $atts["radiobuttons"] = "yes";
        }

        if($f['type'] == 'date')
            $nf['type'] = 'date';

        if($f['type'] == 'dateu')
        {
            $nf['type'] = 'date';
            $nf['unknownallowed'] = 'yes';
        }

        if($f['type'] == 'sqlnchoose')
            $nf['type'] = 'sqlnchoose';
        if($f['type'] == 'sqlschoose')
            $nf['type'] = 'sqlschoose';

        if($nf['type'] == '')
            continue;

        $nf['sqlname'] = $f['sql'];
        $nf['description'] = $f['text'];
        $nf['title'] = $f['text'];

        if(isset($f['default']))
            $nf['default'] = $f['default'];

        if(isset($f['values']))
        {
            $nf['selectables'] = [];
            foreach($f['values'] as $vi => $vv)
                $nf['selectables'][] = [$vi => $vv];
        }

        if(isset($f['check_noempty']))
        {
            if(!isset($nf['validators']))
                $nf['validators'] = [];
            $nf['validators'][] = ['type' => 'notempty',
                                   'failmessage' => $f['check_noempty'],
                                  ];
        }

        if(isset($f['check_regex']))
        {
            if(!isset($nf['validators']))
                $nf['validators'] = [];
            foreach($f['check_regex'] as $rk => $rv)
                if($rk != '' && $rv != '')
                {
                    $regex = $rk;
                    if(substr($rk,0,1) == "/" &&  substr($rk,-1) == "/")
                        $regex = substr($rk,1,strlen($rk) - 2);
                    if(substr($rk,0,1) == "/" &&  substr($rk,-2) == "/u")
                        $regex = substr($rk,1,strlen($rk) - 3);
                    $nf['validators'][] = ['type' => 'regex',
                                           'failmessage' => $rv,
                                           'attributes' => [['valid_regex' => $regex]],
                                          ];
                }
        }

        if(isset($f['minimum']))
        {
            $atts['minimum'] = $f['minimum'];
            if(!isset($nf['validators']))
                $nf['validators'] = [];
            $nf['validators'][] = ['type' => 'range',
                                           'failmessage' => 'Exceed the minimum',
                                           'attributes' => [['minimum' => $f['minimum']]],
                                          ];
        }

        if(isset($f['maximum']))
        {
            $atts['maximum'] = $f['maximum'];
            if(!isset($nf['validators']))
                $nf['validators'] = [];
            $nf['validators'][] = ['type' => 'range',
                                           'failmessage' => 'Exceed the maximum',
                                           'attributes' => [['maximum' => $f['maximum']]],
                                          ];
        }

        if(isset($f['color']))
            $atts['color'] = substr($f['color'],1);

        if(isset($f['prefix']))
            $atts['txt_before'] = $f['prefix'];

        if(isset($f['suffix']))
            $atts['txt_after']  = $f['suffix'];

        if(isset($atts['txt_before']) &&
           isset($atts['txt_after']) &&
           substr($atts['txt_before'],0,1) == '<' &&
           substr($atts['txt_before'],-1)  == '>'  &&
           substr($atts['txt_after'],0,2)  == '</' &&
           substr($atts['txt_after'],-1)   == '>'   &&
           substr($atts['txt_before'],1,strlen($atts['txt_before']) - 2) == substr($atts['txt_after'],2,strlen($atts['txt_after']) - 3)
          )
        {
            //Enclosing value in a html tag will not work, so zero it...
            $atts['txt_before'] = '';
            $atts['txt_after']  = '';
        }

        if(isset($f['gsafe2_attributes']))
        {
            foreach($f['gsafe2_attributes'] as $ai => $av)
                $atts[$ai] = $av;
        }

        if(!empty($atts))
        {
            $nf['attributes'] = [];
            foreach($atts as $ai => $av)
                $nf['attributes'][] = [$ai => $av];
        }

        $fields[] = $nf;

    }
    $r['fields'] = $fields;
    return $r;
}
