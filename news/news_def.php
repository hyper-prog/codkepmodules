<?php
/*  CodKep news module
 *
 *  Module name: news
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

class CodkepNewsNodeDefinition {
    public static $definition;
}

CodkepNewsNodeDefinition::$definition = [
    "name" => "news",
    "table" => "news",
    "show" => "div",
    "access_earlyblock" => true,
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
        110 => [
            "sql" => "sumtemplate",
            "text" => t('News summary template'),
            "type" => "txtselect",
            "par_sec" => "text1ns",
            "form_options" => [
                "id" => "news_sumtemplate",
            ],
            "values" => [],
        ],
        120 => [
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
        130 => [
            "sql" => "sump",
            "text" => t('Parameters of summary line by line or separated by semicolon'),
            "type" => "largetext",
            "par_sec" => "free",
            "row" => 3,
            "col" => 80,
            "form_options" => ["size" => 50],
        ],
        150 => [
            "sql" => "sumpreview",
            "text" => t("Preview of the summary"),
            "default" => t("There is no preview of summary"),
            "type" => "static",
            "form_options" => [
                "id" => "news_sumpreview",
            ],
            "after" => "<hr/>",
        ],
        210 => [
            "sql" => "fulltemplate",
            "text" => t('News full body template'),
            "type" => "txtselect",
            "par_sec" => "text1ns",
            "form_options" => [
                "id" => "news_fulltemplate",
            ],
            "values" => [],
        ],
        220 => [
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
        230 => [
            "sql" => "fullp",
            "text" => t('Parameters of full body line by line or separated by semicolon'),
            "type" => "largetext",
            "par_sec" => "free",
            "row" => 3,
            "col" => 80,
            "form_options" => ["size" => 50],
        ],
        250 => [
            "sql" => "fullpreview",
            "text" => t("Preview of the full body"),
            "default" => t("There is no preview of full body"),
            "type" => "static",
            "form_options" => [
                "id" => "news_fullpreview",
            ],
            "after" => "<hr/>",
        ],

        500 => [
            "sql" => "modified",
            "type" => "timestamp_mod",
            "text" => t('Modification time'),
            "readonly" => true,
        ],
        510 => [
            "sql" => "moduser",
            "type" => "modifier_user",
            "text" => t('Modifier user'),
        ],
        610 => [
            "sql" => "submit_addpreview",
            "type" => "submit",
            "default" => t('Preview'),
            "centered" => true,
            "in_mode" => "insert",
            "dryaction" => "yes",
            "formatters" => "before",
            "suffix" => "&nbsp;&nbsp;&nbsp;",
        ],
        620 => [
            "sql" => "submit_add",
            "type" => "submit",
            "default" => t('Create'),
            "centered" => true,
            "in_mode" => "insert",
            "formatters" => "after",
        ],
        710 => [
            "sql" => "submit_editpreview",
            "type" => "submit",
            "default" => t('Preview'),
            "centered" => true,
            "in_mode" => "update",
            "dryaction" => "yes",
            "formatters" => "before",
            "suffix" => "&nbsp;&nbsp;&nbsp;",
        ],
        720 => [
            "sql" => "submit_edit",
            "type" => "submit",
            "default" => t('Save'),
            "centered" => true,
            "in_mode" => "update",
            "formatters" => "after",
        ],
        810 => [
            "sql" => "submit_del",
            "type" => "submit",
            "default" => t('Delete'),
            "centered" => true,
            "in_mode" => "delete",
        ],
    ],
];
