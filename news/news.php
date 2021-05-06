<?php
/*  CodKep news modules
 *
 *  Module name: news
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function hook_news_boot()
{
    global $site_config;
    $site_config->news_show_control_on_top = true;
    $site_config->news_show_internal_full_topcss = 'news-internal-view-full';

    $site_config->news_define_newspath = true;
    $site_config->news_newspath_base = 'news';
}

function hook_news_defineroute()
{
    global $site_config;
    if(!$site_config->news_define_newspath)
        return [];
    return [
        ['path' => $site_config->news_newspath_base . '/{newspath}','callback' => 'pc_newsbypath']
    ];
}

function pc_newsbypath()
{
    global $user;

    par_def('newspath','text0sudne');
    $newspath = par('newspath');
    $r = db_query('news')
        ->get('newsid')
        ->cond_fb('published')
        ->cond_fv('path',$newspath,'=')
        ->length(1)
        ->execute_to_arrays(["noredirect" => true]);
    if(!isset($r[0]['newsid']) || $r[0]['newsid'] == '')
        load_loc('notfound');

    $node = node_load_intype($r[0]['newsid'],'news');
    if(node_access($node,'view',$user) == NODE_ACCESS_ALLOW)
        return $node->view();
    return '';
}

function news_news_view(Node $node)
{
    global $user;
    global $site_config;

    ob_start();
    set_title($node->title);
    add_css_file(codkep_get_path('news','web').'/news.css');

    print '<section class="'.$site_config->news_show_internal_full_topcss.'">';
    print implode('',run_hook('newsview_before',$node));
    print '<div class="news-show-titleline">';
    print '<div class="news-title-str">';
    print '<h1>' . $node->title . '</h1>';
    print '</div>';

    if($site_config->news_show_control_on_top)
    {
        print '<div class="news-control-btns">';
        if(node_access($node,'update',$user) == NODE_ACCESS_ALLOW)
        {
            print '<div class="news-control-edit">';
            print l('<img class="pe-btn-img btn-img" src="' . codkep_get_path('core', 'web') . '/images/edit35.png"/>',
                'node/' . $node->node_nid . '/edit',
                ['title' => t('Edit this news')]);
            print '</div>';
        }
        if(node_access($node,'delete',$user) == NODE_ACCESS_ALLOW)
        {
            print '<div class="news-control-del">';
            print l('<img class="pd-btn-img btn-img" src="' . codkep_get_path('core', 'web') . '/images/del35.png"/>',
                'node/' . $node->node_nid . '/delete',
                ['title' => t('Delete this news')]);
            print '</div>';
        }
        print '</div>';
    }
    print '</div>';
    print '<small>'.t('Published on _publishdatetime_',['_publishdatetime_' => $node->node_created]).'</small>';
    print implode('',run_hook('newsview_aftertitle',$node));
    print $node->fullbody;
    print implode('',run_hook('newsview_after',$node));
    print '</section>';
    return ob_get_clean();
}

function news_list_block($overrides = [])
{
    global $site_config;
    $opts = [
        'adminlnks'       => true,
        'show-notpubished'=> false,
        'sort'            => ['node','created'],
        'length'          => 5,
        'start'           => 0,
        'newsbefore'      => '<section class="news_list_item">',
        'newsafter'       => '</section>',
        'show'            => 'sumbody',
        'separator'       => '<hr class="news_list_sepline"/>',
        'titlebefore'     => '<h2>',
        'titleafter'      => '</h2>',
        'next-on-top'     => true,
        'back-on-top'     => false,
        'next-on-bottom'  => false,
        'back-on-bottom'  => true,
        'whole-top-css'   => 'news-sum-list',
        'next-back-url'   => current_loc(),
    ];

    foreach($opts as $optname => $optval)
        if(isset($overrides[$optname]))
            $opts[$optname] = $overrides[$optname];

    $q = node_query('news')
        ->get_a(['newsid','title','path','sumbody','fullbody'])
        ->counting(['node','nid'],'cnt');
    if(!$opts['show-notpubished'])
        $q->cond_fb('published');
    $cntr = $q->execute_to_arrays();
    if(!isset($cntr[0]['cnt']) || intval($cntr[0]['cnt']) == 0)
    {
        return '<div class="no-news-class">'.t('No news').'</div>';
    }

    $allcnt = intval($cntr[0]['cnt']);

    $q = node_query('news')
          ->get_a(['newsid','title','path','sumbody','fullbody'])
          ->get(['node','created'],'ncreated')
          ->sort($opts['sort'],['direction' => 'REVERSE'])
          ->start($opts['start'])
          ->length($opts['length']);
    if(!$opts['show-notpubished'])
        $q->cond_fb('published');
    $nws = $q->execute_to_arrays();
    $cnt = 0;

    add_css_file(codkep_get_path('news','web').'/news.css');

    ob_start();
    print '<section class="'.$opts['whole-top-css'].'">';
    if($opts['next-on-top'] || $opts['back-on-top'])
    {
        print '<div style="display: flex; align-items: center; justify-content: center;">';
        print '<div style="display: inline-flex; margin-left: auto; margin-right:auto;">';
        if($opts['start'] > 0 && $opts['next-on-top'])
            print news_list_block_nextback_link($opts['next-back-url'],'newer',$opts['start'],$opts['length'],$allcnt);
        if($opts['start'] + $opts['length'] < $allcnt && $opts['back-on-top'])
            print news_list_block_nextback_link($opts['next-back-url'],'older',$opts['start'],$opts['length'],$allcnt);
        print '</div>';
        print '</div>';
    }

    foreach($nws as $nw)
    {
        if($cnt > 0)
            print $opts['separator'];
        print $opts['newsbefore'];
        print '<div class="news-list-block-titleline">';
            print '<div class="newslb-title-str">';
            $full_news_path = 'node/'.$nw['node_nid'];
            if($site_config->news_define_newspath && $nw['path'] != '')
                $full_news_path = $site_config->news_newspath_base . '/' . $nw['path'];
            print $opts['titlebefore'] . l($nw['title'],$full_news_path).$opts['titleafter'];
            print '</div>';
            print '<div class="newslb-control-btns">';
            if($opts['adminlnks'])
            {
                print '<div class="newslb-control-edit">';
                print l('<img class="nebtn-img" src="'.codkep_get_path('core','web').'/images/edit35.png"/>',
                        'node/'.$nw['node_nid'].'/edit',
                        ['title' => t('Edit this news')]);
                print '</div>';
                print '<div class="newslb-control-del">';
                print l('<img class="nebtn-img" src="'.codkep_get_path('core','web').'/images/del35.png"/>',
                        'node/'.$nw['node_nid'].'/delete',
                        ['title' => t('Delete this news')]);
                print '</div>';
            }
            print '</div>';
        print '</div>';
        print '<small>'.t('Published on _publishdatetime_',['_publishdatetime_' => $nw['ncreated']]).'</small>';
        print $nw[$opts['show']];
        print $opts['newsafter'];
        print '<div class="c"></div>';
        $cnt++;
    }

    if($opts['next-on-bottom'] || $opts['back-on-bottom'])
    {
        print '<div style="display: flex; align-items: center; justify-content: center;">';
        print '<div style="display: inline-flex; margin-left: auto; margin-right:auto;">';
        if($opts['start'] > 0 && $opts['next-on-bottom'])
            print news_list_block_nextback_link($opts['next-back-url'],'newer',$opts['start'],$opts['length'],$allcnt);
        if($opts['start'] + $opts['length'] < $allcnt && $opts['back-on-bottom'])
            print news_list_block_nextback_link($opts['next-back-url'],'older',$opts['start'],$opts['length'],$allcnt);
        print '</div>';
        print '</div>';
    }
    print '</section>';
    return ob_get_clean();
}

function news_list_block_nextback_link($url,$what,$start,$length,$allcnt)
{
    if($what == 'newer')
    {
        $newoffset = $start - $length;
        if($newoffset < 0)
            $newoffset = 0;
        return l(t('View newer news'),$url, ['class' => 'nsl-nb-lnk'],['newsoffset' => $newoffset]);
    }
    if($what == 'older')
    {
        $newoffset = $start + $length;
        if($newoffset > $allcnt)
            $newoffset = $allcnt - 1;
        return l(t('View older news'),$url, ['class' => 'nsl-nb-lnk'], ['newsoffset' => $newoffset]);
    }
    return '';
}

function news_manage_news($overrides = [])
{
    $opts = [
        'adminlnks'    => true,
        'query-notpub' => true,
        'query-sort'   => ['node','created'],
        'query-length' => 99,
        'query-start'  => 0,
        'tableclass'   => 'news_manage_news_table',
    ];

    foreach($opts as $optname => $optval)
        if(isset($overrides[$optname]))
            $opts[$optname] = $overrides[$optname];

    $q = node_query('news')
        ->get_a(['newsid','title','path','published'])
        ->get(['node','created'],'ncreated')
        ->get(['node','creator'] ,'ncreatuser')
        ->get(['news','modified'],'news_modified')
        ->get(['news','moduser'],'news_moduser')
        ->sort($opts['query-sort'],['direction' => 'REVERSE'])
        ->start($opts['query-start'])
        ->length($opts['query-length']);
    if(!$opts['query-notpub'])
        $q->cond_fb('published');
    $nws = $q->execute_to_arrays();

    $c = [
        '#tableopts' => ['class' => $opts['tableclass']],
        '#fields' => ['newsid','title','published','created','modified','edit'],
        'newsid' => [
            'headertext' => t('NewsId'),
            'valuecallback' => function($r) {
                return $r['newsid'] . ' (' . l(t('View'),'node/'.$r['node_nid']) . ')';
            },
        ],
        'title' => [
            'headertext' => t('Headline'),
        ],
        'published' => [
            'headertext' => t('Published'),
            'valuecallback' => function($r) {
                return $r['published'] ? t('Yes') : t('No');
            },
        ],
        'created' => [
            'headertext' => t('Created'),
            'valuecallback' => function($r) {
                $usr = $r['ncreatuser'];
                if($usr == '')
                    $usr = t('Unknown');
                return $usr . ' - ' . $r['ncreated'];
            },
        ],
        'modified' => [
            'headertext' => t('Last modified'),
            'valuecallback' => function($r) {
                $usr = $r['news_moduser'];
                if($usr == '')
                    $usr = t('Unknown');
                return $usr . ' - ' . $r['news_modified'];
            },
        ],
        'edit' => [
            'headertext' => '',
            'valuecallback' => function($r) {
                return l('<img class="nebtn-img" src="'.codkep_get_path('core','web').'/images/edit20.png" />',
                         'node/'.$r['node_nid'].'/edit',['title' => t('Edit news')] ) . ' ' .
                       l('<img class="nebtn-img" src="'.codkep_get_path('core','web').'/images/del20.png" />',
                         'node/'.$r['node_nid'].'/delete',['title' => t('Delete news')]);
            },
        ],
    ];

    ob_start();
    print l('<img src="'.codkep_get_path('core','web').'/images/small_green_plus.png"/>',
            "node/news/add",
            ['title' => t('Upload a news')]);
    $data = [];
    print to_table($nws,$c,$data);
    if($data['rowcount'] > 5)
        print l('<img src="'.codkep_get_path('core','web').'/images/small_green_plus.png"/>',
                "node/news/add",
                ['title' => t('Upload a news')]);
    return ob_get_clean();
}

function hook_news_node_access(Node $node,$op,$acc)
{
    if($node->node_type == 'news')
    {
        if(in_array($op,['create','delete','update']))
        {
            if($acc->role == ROLE_ADMIN || $acc->role == ROLE_EDITOR)
                return NODE_ACCESS_ALLOW;
            return NODE_ACCESS_DENY;
        }
        return NODE_ACCESS_ALLOW;
    }
    return NODE_ACCESS_IGNORE;
}

function hook_news_node_saved($obj)
{
    if($obj->node_ref->node_type == 'news')
        ccache_delete('routecache');
}

function hook_news_node_deleted($nid,$type,$join_id)
{
    if($type == 'news')
        ccache_delete('routecache');
}

function hook_news_node_inserted($obj)
{
    if($obj->node_ref->node_type == 'news')
        ccache_delete('routecache');
}

function validator_news_path(&$path,$def,$values)
{
    $origpath  = $path;
    $checkpath = $origpath;
    $rup = 0;
    do
    {
        if($rup > 0)
            $checkpath = $origpath . "-" . sprintf("%03d",$rup);

        $q = db_query('news')
            ->counting('newsid','count')
            ->cond_fv('path',$checkpath,'=');
        if(isset($values['newsid']) && $values['newsid'] != null && $values['newsid'] != '')
            $q->cond_fv('newsid',$values['newsid'],'!=');
        $c = $q->execute_to_single();
        $rup++;
    }
    while($c > 0);
    $path = $checkpath;
}


function hook_news_nodetype()
{
    $n = [];
    $n['news'] = [
        "name" => "news",
        "table" => "news",
        "show" => "div",
        "div_class" => "news_edit_area",
        "view_callback" => "news_news_view",
        "javascript_files" => [codkep_get_path('core','web') . '/ckeditor/ckeditor.js'],
        "form_script" => "window.onload = function() {
                              CKEDITOR.replace('news_sumbody_ckedit');
                              CKEDITOR.replace('news_body_ckedit');
                          };
                          jQuery(document).ready(function() {
                              jQuery('.autopath').each(function() {
                                  codkep_set_autofill(this);
                              });
                          });",
        "fields" => [
            10 => [
                "sql" => "newsid",
                "type" => "keyn",
                "text" => t('News identifier'),
                "hide" => true,
            ],
            20 => [
                "sql" => "title",
                "text" => t('Headline'),
                "type" => "smalltext",
                "form_options" => [
                    "size" => 60,
                    "id" => "news-title-edit",
                ],
                "par_sec" => "text4",
            ],
            30 => [
                "sql" => "path",
                "text" => t('News path (location)'),
                "type" => "smalltext",
                "par_sec" => "text0sudne",
                "form_options" => [
                    "size" => 60,
                    "class" => "autopath",
                    "rawattributes" => "data-autopath-from=\"news-title-edit\" data-autopath-type=\"alsd\"",
                ],
                'check_callback' => 'validator_news_path',
            ],
            40 => [
                "sql" => "published",
                "text" => t('Published'),
                "type" => "check",
                "default" => false,
            ],
            50 => [
                "sql" => "sumbody",
                "text" => t('News summary body html'),
                "type" => "largetext",
                "par_sec" => "free",
                "row" => 18,
                "col" => 80,
                "form_options" => [
                    "id" => "news_sumbody_ckedit",
                ],
            ],
            60 => [
                "sql" => "fullbody",
                "text" => t('News full body html'),
                "type" => "largetext",
                "par_sec" => "free",
                "row" => 25,
                "col" => 80,
                "form_options" => [
                    "id" => "news_body_ckedit",
                ],
            ],

            100 => [
                "sql" => "modified",
                "type" => "timestamp_mod",
                "text" => t('Modification time'),
                "readonly" => true,
            ],
            110 => [
                "sql" => "moduser",
                "type" => "modifier_user",
                "text" => t('Modifier user'),
            ],
            200 => [
                "sql" => "submit_add",
                "type" => "submit",
                "default" => t('Create'),
                "centered" => true,
                "in_mode" => "insert",
            ],
            210 => [
                "sql" => "submit_edit",
                "type" => "submit",
                "default" => t('Save'),
                "centered" => true,
                "in_mode" => "update",
            ],
            220 => [
                "sql" => "submit_del",
                "type" => "submit",
                "default" => t('Delete'),
                "centered" => true,
                "in_mode" => "delete",
            ],
        ],
    ];

    return $n;
}

function hook_news_introducer()
{
    global $user;

    if(!$user->auth || $user->role != ROLE_ADMIN)
        return ['News' => ''];

    $html = l(t('Upload a news'),'node/news/add');
    return ['News' => $html];
}

function hook_news_documentation($section)
{
    $docs = [];
    return $docs;
}

