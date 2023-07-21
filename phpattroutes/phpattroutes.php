<?php
/*  CodKep php8 attribute defined routes module
 *
 *  Module name: phpattroutes
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_phpattroutes_defineroute()
{
    $routes = [];
    global $sys_data;

    $sys_data->attribute_defined_routes = ccache_get('attribroutes');
    if($sys_data->attribute_defined_routes != NULL)
        return $sys_data->attribute_defined_routes;

    $user_defined_functions = get_defined_functions()["user"];
    foreach($user_defined_functions as $funcname)
    {
        if($funcname[0] == 'l' &&
           isset($funcname[1]) && $funcname[1] == 'o' &&
           isset($funcname[2]) && $funcname[2] == 'c' &&
           isset($funcname[3]) && $funcname[3] == '_' )
        {
            $refl = new ReflectionFunction($funcname);
            $att_ckpath = $refl->getAttributes('ckpath');

            if(count($att_ckpath) < 1)
                continue;
            $att_ckpath_args = $att_ckpath[0]->getArguments();
            if(count($att_ckpath_args) < 1)
                continue;

            $route = [
                'path' => $att_ckpath_args[0],
                'callback' => $funcname,
            ];

            $att_cktype  = $refl->getAttributes('cktype');
            if(count($att_cktype) > 0)
            {
                $att_cktype_args = $att_cktype[0]->getArguments();
                if(count($att_cktype_args) > 0)
                    $route['type'] = $att_cktype_args[0];
            }
            $att_cktitle = $refl->getAttributes('cktitle');
            if(count($att_cktitle) > 0)
            {
                $att_cktitle_args = $att_cktitle[0]->getArguments();
                if(count($att_cktitle_args) > 0)
                    $route['title'] = $att_cktitle_args[0];
            }
            $att_cktheme = $refl->getAttributes('cktheme');
            if(count($att_cktheme) > 0)
            {
                $att_cktheme_args = $att_cktheme[0]->getArguments();
                if(count($att_cktheme_args) > 0)
                    $route['theme'] = $att_cktheme_args[0];
            }
            array_push($routes,$route);
        }
    }

    run_hook('attribute_defined_routes_generated');
    ccache_store('attribroutes',$sys_data->attribute_defined_routes,3600);
    return $routes;
}


function hook_phpattroutes_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = [
            'Phpattroutes' => [
                'path' => codkep_get_path('phpattroutes','server') . '/phpattroutes.mdoc',
                'index' => false ,
                'imagepath' => codkep_get_path('phpattroutes','web') .'/docimages'
            ]
        ];
    }
    return $docs;
}

