<?php
/*  ActivityComment module for CodKep
 *
 *  Module name: activitycomment
 *  Dependencies: sql,user,forms
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

define('ACTIVITYCOMMENT_ACCESS_IGNORE',0);
define('ACTIVITYCOMMENT_ACCESS_ALLOW',1);
define('ACTIVITYCOMMENT_ACCESS_DENY',2);

function hook_activitycomment_boot()
{
    global $site_config;
    $site_config->comment_containers = [];

    $site_config->comment_delete_own_until_sec = 60*60; //1 hour
    $site_config->acitvity_comment_block_css_class = 'commentblk_default_style';
    $site_config->acitvity_comment_renderer_callback = 'codkep_render_commentblock';
}

function hook_activitycomment_before_start()
{
    add_css_file(codkep_get_path('activitycomment','web') . '/activitycomment.css');
}

function register_comment_container($containername)
{
    global $site_config;
    if(check_str($containername,'text0nsne'))
    {
        if(!in_array($containername, $site_config->comment_containers))
            array_push($site_config->comment_containers, $containername);
        return;
    }
    load_loc('error','Illegal comment container class (text0nsne allowed)','Internal activity module error');
}

function hook_activitycomment_defineroute()
{
    return [
        ['path' => 'addnewcommentajax'        ,'callback' => 'addnewcomment_comment' ,'type' => 'ajax'],
        ['path' => 'delcommentajax'           ,'callback' => 'delcomment_comment'    ,'type' => 'ajax'],
    ];
}

function comment_access($container,$refid,$op,$account)
{
    if(!in_array($op,['view','add']))
        return ACTIVITYCOMMENT_ACCESS_DENY;
    $n = run_hook('comment_access',$container,$refid,$op,$account);

    if(in_array( ACTIVITYCOMMENT_ACCESS_DENY,$n))
        return ACTIVITYCOMMENT_ACCESS_DENY;
    if(in_array( ACTIVITYCOMMENT_ACCESS_ALLOW,$n))
        return ACTIVITYCOMMENT_ACCESS_ALLOW;

    //Default comment permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return ACTIVITYCOMMENT_ACCESS_ALLOW;
    // Allows view for everyone. (You can disable by send DENY from a hook.)
    if($op == 'view')
        return ACTIVITYCOMMENT_ACCESS_ALLOW;
    return ACTIVITYCOMMENT_ACCESS_DENY;
}

function get_comment_block($container,$refid)
{
    global $user;
    global $user_module_settings;
    global $site_config;

    if(!in_array($container,$site_config->comment_containers))
        return '';
    if(comment_access($container,$refid,'view',$user) != ACTIVITYCOMMENT_ACCESS_ALLOW)
        return '';

    $usrtable = $user_module_settings->sql_tablename;
    $usrnamecol = $user_module_settings->sql_name_column;
    ob_start();
    $r = sql_exec("SELECT cid,cmmt.uid,created,body,ut.$usrnamecol AS uname
                   FROM comment_$container AS cmmt
                   INNER JOIN $usrtable AS ut ON ut.uid = cmmt.uid
                   WHERE ref = :refid
                   ORDER BY created",
            [':refid' => $refid]);
    $count = 0;

    print '<div class="'.$site_config->acitvity_comment_block_css_class.'">'; //changeable upper container css class
    print "<div id=\"fullcommentarea_$refid\" class=\"comment_module_fullc_area fullcommentblock_width\">";
    print "<div id=\"showcommentarea_$refid\" class=\"comment_module_comment_area\">";
    $t_curr = time();
    while($rec = $r->fetch())
    {
        $erasable = false;
        if($user->uid == $rec['uid'])
        {
            $t_created = (new DateTime($rec['created']))->format('U');
            if(($t_curr-$t_created) < $site_config->comment_delete_own_until_sec  &&
               comment_access($container,$refid,'add',$user) == ACTIVITYCOMMENT_ACCESS_ALLOW )
                $erasable = true;
        }
        print call_user_func_array($site_config->acitvity_comment_renderer_callback,
                    [$container,$rec['cid'],
                     $rec['uname'],
                     $rec['created'],
                     $rec['body'],
                     $erasable]);
        ++$count;
    }
    print "</div>"; // .comment_module_comment_area #showcommentarea_$refid

    if(comment_access($container,$refid,'add',$user) == ACTIVITYCOMMENT_ACCESS_ALLOW)
    {
        $f = new HtmlForm('addcomment');
        $f->opts(['class' => 'cadder_form_cmts']);
        $f->action_ajax("addnewcommentajax");
        $f->hidden('ref', $refid);
        $f->hidden('cont', $container);
        $f->text('t0', '<div class="cadder_visible_parts addcommentblock_width">');
        $f->textarea('commenttext', '', 2, 40, ['id' => 'new_comment_area', 'class' => 'new_comment_body_box']);
        $f->input('submit', 'send', t("Send"), ['class' => 'new_comment_send_btn']);
        $f->text('t1', '</div>');
        print '<div class="addnew_c_form">' . $f->get() . '</div>';
    }
    print "</div>"; //.comment_module_fullc_area #fullcommentarea_$refid
    print '</div>'; //changeable upper container css class
    return ob_get_clean();
}

function addnewcomment_comment()
{
    global $user;
    global $site_config;

    form_source_check();
    par_def('commenttext','text5');
    par_def('ref','number0');
    par_def('cont','text0nsne');

    $container = par('cont');
    $refid = par('ref');
    $bodytext = par('commenttext');

    if(!in_array($container,$site_config->comment_containers))
        return;
    if($bodytext == '')
        return;
    if(comment_access($container,$refid,'add',$user) != ACTIVITYCOMMENT_ACCESS_ALLOW)
        return;

    global $db;
    sql_exec("INSERT INTO comment_$container(ref,uid,created,body)
              VALUES(:refid,:uid,".sql_t('current_timestamp').",:bodytxt)",
                [':refid' => $refid,
                 ':uid' => $user->uid,
                 ':bodytxt' => $bodytext ] );
    $cid = $db->sql->lastInsertId('cid');
    ajax_add_append("#showcommentarea_$refid",
        call_user_func_array($site_config->acitvity_comment_renderer_callback,
                         [$container,$cid,$user->name,t('Just now'),
                          $bodytext,$site_config->comment_delete_own_until_sec > 0]));
    ajax_add_val('#new_comment_area','');
}

function delcomment_comment()
{
    global $user;
    global $site_config;

    if($user->auth)
    {
        par_def('id', 'number0');
        $cid = par('id');
        par_def('cont', 'text0nsne');
        $container = par('cont');

        if(!in_array($container, $site_config->comment_containers))
            return;

        $r = sql_exec_fetchN("SELECT cid,cmmt.uid,created,ref
                              FROM comment_$container AS cmmt
                              WHERE cid = :cidp",
                            [':cidp' => $cid]);
        if(!isset($r) || $r == null)
            return;
        if($r['uid'] != $user->uid)
            return;
        if(comment_access($container,$r['ref'],'add',$user) != ACTIVITYCOMMENT_ACCESS_ALLOW)
            return;
        $t_curr = time();
        $t_created = (new DateTime($r['created']))->format('U');
        if(($t_curr-$t_created) >= $site_config->comment_delete_own_until_sec)
            return;
        sql_exec("DELETE FROM comment_$container
                  WHERE cid = :cidp",
                  [':cidp' => $cid]);
        ajax_add_remove('#cmt_'.$cid.'_idx');
    }
}

function codkep_render_commentblock($cont,$cid,$name,$created,$text,$deletelink)
{
    ob_start();
    print '<div class="commentitem" id="cmt_'.$cid.'_idx">';
    print '<div class="commentheader">';
    print '<div class="commentername">'.$name.'</div>';

    if($deletelink)
        print l("✕",'delcommentajax',['class' => 'use-ajax comment_delete_ajax_lnk',
                'title' => t('Delete this comment')],
            ['cont' => $cont,'id' => $cid]);
    print '<div class="timeofcomm">'.$created.'</div>';
    print '</div>'; //.commentheader
    print '<div class="bodytext">'.$text.'</div>';

    print "</div>";
    return ob_get_clean();
}

function hook_activitycomment_required_sql_schema()
{
    global $site_config;
    $t = [];
    foreach($site_config->comment_containers as $cnt)
    {
        $t["activitycomment_module_comment_table_$cnt"] =
            [
                "tablename" => "comment_$cnt",
                "columns" => [
                    'cid' => 'SERIAL',
                    'ref' => 'BIGINT UNSIGNED',
                    'uid' => 'BIGINT UNSIGNED',
                    'created' => 'TIMESTAMP',
                    'body' => sql_t('longtext_type'),
                ],
            ];
    }
    return $t;
}


function hook_activitycomment_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = [
            'activitycomment' => [
                'path' => codkep_get_path('activitycomment','server') . '/activitycomment.mdoc',
                'index' => false ,
                'imagepath' => codkep_get_path('activitycomment','web') .'/docimages'
            ]
        ];
    }
    return $docs;
}

//end.
