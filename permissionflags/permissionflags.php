<?php
/*  CodKep permission flags module
 *
 *  Module name: permissionflags
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_permissionflags_boot()
{
    global $user_permission_flags;

    $user_permission_flags = [];

    /*  $user_permission_flags = [
     *      'perm_createsome'  => 'Permission to create something',
     *      'perm_editsome'    => 'Permission to edit something',
     *      'perm_createother' => 'Permission to create other things',
     *      'perm_editother'   => 'Permission to edit other things',
     *  ];
     *
     */
}

function hook_permissionflags_nodetype_alter_user($p,$reason)
{
    global $user_permission_flags;

    if($reason != "loaded")
        return;

    $idx = 200;
    foreach($user_permission_flags as $flagcode => $flaxtext)
    {
        $p->def['fields'][$idx] = [
            'sql'  => $flagcode,
            'text' => $flaxtext,
            'type' => 'check',
        ];
        $idx++;
    }
}

function hook_permissionflags_user_identified()
{
    global $user;
    global $user_module_settings;
    global $user_permission_flags;

    $perm_flag_names = array_keys($user_permission_flags);
    $user->permflags = [];
    if(count($perm_flag_names) > 0)
    {
        $pfs = db_query($user_module_settings->sql_tablename)
                    ->get_a($perm_flag_names)
                    ->cond_fv('uid',$user->uid,'=')
                    ->execute_to_arrays(["noredirect" => true]);

        if(isset($pfs[0]))
            foreach($perm_flag_names as $pfn)
                $user->permflags[$pfn] = ($pfs[0][$pfn] ? true : false);
    }
}

function permission_hasflag($flag,$u = null)
{
    if($u == null)
    {
        global $user;
        $u = $user;
    }
    if(isset($u->permflags) && array_key_exists($flag,$u->permflags) && $u->permflags[$flag] === true)
        return true;
    return false;
}

