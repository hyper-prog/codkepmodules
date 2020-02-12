
function insertToMyCurrentEditor(text)
{
    if(text == '')
        return;

    var em = jQuery('#rq_editormode').val();
    if(em == 't')
        rawQEditorInsertAtCaret('sql_edit_input',text);
    if(em == 'm')
        insertToMonacoEditor(text);

    jQuery('#rqedit_save_button').addClass('rawqsqlchanged');
}

function rqeditFieldrepbuttonActivated(deftext,conttext,local_texts)
{
    if(deftext == '' || typeof deftext === 'undefined')
    {
        insertToMyCurrentEditor('#' + conttext);
        return;
    }

    dlgbody = '';

    if(deftext[0] != '#')
        return;
    var parts = deftext.split('#');
    if(parts.length < 3)
        return;

    dlgbody += '<input type="hidden" id="rqe_fdlg_alldefinition" value="'+deftext+'" />';
    dlgbody += '<div><table class="rqe_fdlg_htable" style="border-collapse: collapse; border: 1px solid #888888;">';

    dlgbody += '<tr>';
    dlgbody += '<th></th>';
    dlgbody += '<th>'+local_texts[0]+'</th>';
    dlgbody += '<th align="left">'+local_texts[1]+'</th>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td align="left">'+local_texts[2]+'</td>';
    dlgbody += '<td><input id="rqe_fdlg_keywordcb" type="checkbox" checked disabled></td>';
    dlgbody += '<td align="left"><input id="rqe_fdlg_keywordle" name="fdlg_keywordtext" value="' +
                parts[1] + '" type="text" size="20" disabled></td>';
    dlgbody += '</tr>';

    if(deftext.includes('[HEADTEXT]'))
    {
        dlgbody += '<tr>';
        dlgbody += '<td align="left">'+local_texts[3]+'</td>';
        dlgbody += '<td><input id="rqe_fdlg_headtextcb" type="checkbox" checked></td>';
        dlgbody += '<td align="left"><input id="rqe_fdlg_headtextle" name="fdlg_headtext" value="" type="text" size="40"></td>';
        dlgbody += '</tr>';
    }

    if(deftext.includes('[COLOR]'))
    {
        dlgbody += '<tr>';
        dlgbody += '<td align="left">'+local_texts[4]+'</td>';
        dlgbody += '<td><input id="rqe_fdlg_colcolorcb" type="checkbox" ></td>';
        dlgbody += '<td align="left"><input id="rqe_fdlg_colcolorce" name="fdlg_colcolor" value="#cccccc" type="color" disabled></td>';
        dlgbody += '</tr>';
    }

    if(deftext.includes('[WIDTH]'))
    {
        dlgbody += '<tr>';
        dlgbody += '<td align="left">'+local_texts[5]+'</td>';
        dlgbody += '<td><input id="rqe_fdlg_widthcb" type="checkbox" ></td>';
        dlgbody += '<td align="left"><input id="rqe_fdlg_widthne" name="fdlg_colcolor" value="60" type="number" style="width: 70px;" disabled></td>';
        dlgbody += '</tr>';
    }

    dlgbody += '<tr>';
    dlgbody += '<td align="left" style="font-style: italic;">'+local_texts[6]+'</td>';
    dlgbody += '<td colspan="2"><pre id="rqe_fdlg_preview"></pre></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td colspan="3"><button id="rqe_fdlg_action_cancel" style="font-weight: bold; padding: 5px;">'+local_texts[7]+'</button>';
    dlgbody += '<button id="rqe_fdlg_action_insert" style="font-weight: bold; padding: 5px;">'+local_texts[8]+'</button></td>';
    dlgbody += '</tr>';

    dlgbody += '</table></div>';

    prepare_ckdialog(local_texts[9],dlgbody);
    popup_ckdialog();

    jQuery('#rqe_fdlg_headtextcb').change(function() { rqeditFieldrepUpdatePreview(); });
    jQuery('#rqe_fdlg_colcolorcb').change(function() { rqeditFieldrepUpdatePreview(); });
    jQuery('#rqe_fdlg_widthcb'   ).change(function() { rqeditFieldrepUpdatePreview(); });
    jQuery('#rqe_fdlg_headtextle').on('input',function(e){ rqeditFieldrepUpdatePreview(); });
    jQuery('#rqe_fdlg_colcolorce').on('input',function(e){ rqeditFieldrepUpdatePreview(); });
    jQuery('#rqe_fdlg_widthne'   ).on('input',function(e){ rqeditFieldrepUpdatePreview(); });

    jQuery('#rqe_fdlg_action_cancel').click(function(){
        close_ckdialog();
    });
    jQuery('#rqe_fdlg_action_insert').click(function(){
        insertToMyCurrentEditor('"' + jQuery('#rqe_fdlg_preview').html() + '"');
        close_ckdialog();
    });

    rqeditFieldrepUpdatePreview();
    jQuery('#rqe_fdlg_headtextle').focus();
}

function rqeditFieldrepUpdatePreview()
{
    var deft;
    deft = jQuery('#rqe_fdlg_alldefinition').val();
    if(deft.includes('[HEADTEXT]')) {
        if (jQuery('#rqe_fdlg_headtextcb').is(':checked')) {
            jQuery('#rqe_fdlg_headtextle').prop("disabled", false);
            deft = deft.replace('[HEADTEXT]', jQuery('#rqe_fdlg_headtextle').val());
        }
        else {
            jQuery('#rqe_fdlg_headtextle').prop("disabled", true);
            deft = deft.replace('[HEADTEXT]', '');
        }
    }
    if(deft.includes('[COLOR]')) {
        if (jQuery('#rqe_fdlg_colcolorcb').is(':checked')) {
            jQuery('#rqe_fdlg_colcolorce').prop("disabled", false);
            deft = deft.replace('[COLOR]', jQuery('#rqe_fdlg_colcolorce').val().replace('#', ''));
        }
        else {
            jQuery('#rqe_fdlg_colcolorce').prop("disabled", true);
            deft = deft.replace('[COLOR]', '');
        }
    }
    if(deft.includes('[WIDTH]')) {
        if (jQuery('#rqe_fdlg_widthcb').is(':checked')) {
            jQuery('#rqe_fdlg_widthne').prop("disabled", false);
            deft = deft.replace('[WIDTH]', jQuery('#rqe_fdlg_widthne').val());
        }
        else {
            jQuery('#rqe_fdlg_widthne').prop("disabled", true);
            deft = deft.replace('[WIDTH]', '');
        }
    }
    deft = deft.split('').reverse().join('');
    while(deft[0] == '#')
        deft = deft.substr(1);
    deft = deft.split('').reverse().join('');
    jQuery('#rqe_fdlg_preview').html(deft);
}

function rqeditFireupParstore()
{
    rqeditParseParameterDefinitionString();
    rqeditParameditUpdateParbuttons();

    jQuery('#rqe_add_par_btn').click(function(e) {
        rqeditAddEditParameter('');
        e.preventDefault();
    });
    jQuery('#rqe_showedit_par_btn').click(function(e) {
        rqeditShowEditFullParameterstring();
        e.preventDefault();
    });
    jQuery(document).on('click','.rqe_pedit_btn',function(e) {
        rqeditAddEditParameter(jQuery(this).html());
        e.preventDefault();
    });
    jQuery(document).on('click','.rqe_paredit_parsubs_insert',function(e) {
        insertToMyCurrentEditor(jQuery(this).html());
        e.preventDefault();
    });
}

function rqeditParseParameterDefinitionString()
{
    parstore = [];
    var f = jQuery('#rqe_parameteredit').val();
    var farr = f.split(';');
    for(var i = 0;i < farr.length;i++)
    {
        var parts = farr[i].split(':');
        if(partypes.indexOf(parts[0]) != -1)
            parstore.push({
                type: parts[0],
                keyw: parts[1],
                descr: parts[2],
                extra: (parts.length == 3 ? '' : parts[3] )
            });
    }
}

function rqeditParameditUpdateParbuttons()
{
    htmlcontent = '';
    defstr = '';
    for(var i = 0;i < parstore.length;++i)
    {
        if(defstr != '')
            defstr += ';';
        defstr += parstore[i].type + ':' + parstore[i].keyw + ':' + parstore[i].descr;

        if(partypeopts[parstore[i].type][0])
            defstr += ':' + parstore[i].extra;
        htmlcontent += '<button class="rq_float_left rqe_pedit_btn">'+parstore[i].keyw+'</button>';
    }
    jQuery('.rqe_par_lst_cont').html(htmlcontent);

    if(jQuery('#rqe_parameteredit').val() != defstr)
    {
        jQuery('#rqedit_save_button').addClass('rawqsqlchanged');
        jQuery('#rqe_parameteredit').val(defstr);
    }
}

function rqeditAddEditParameter(pkeyw)
{
    cc = {kw: '', ty: partypes[0], ds: '', ex: ''};
    if(pkeyw != '')
        for(var i=0;i<parstore.length;++i)
            if(parstore[i].keyw == pkeyw)
            {
                cc.kw = parstore[i].keyw;
                cc.ty = parstore[i].type;
                cc.ds = parstore[i].descr;
                cc.ex = parstore[i].extra;
                break;
            }

    dlgbody = '';

    dlgbody += '<div><table class="rqe_fdlg_htable" style="border-collapse: collapse; border: 1px solid #888888;">';

    dlgbody += '<tr>';
    dlgbody += '<td align="left">'+local_texts[11]+'</td>';
    dlgbody += '<td align="left"><select name="rqe_paredit_typesel" id="rqe_paredit_typesel">';
    for(var i = 0;i < partypes.length;++i)
        dlgbody += '<option value="'+partypes[i]+'"'+ (partypes[i] == cc.ty ? ' selected' : '') +'>'+partypes[i]+'</option>';
    dlgbody += '</select></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td align="left">'+local_texts[12]+'</td>';
    dlgbody += '<td align="left"><input id="rqe_paredit_keywordib" type="text" value="'+cc.kw+'" size="40"></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td align="left" id="rqe_extraline_descr"></td>';
    dlgbody += '<td align="left"><input id="rqe_paredit_typeextra" type="text" value="'+cc.ex+'" size="40" disabled></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td align="left">'+local_texts[13]+'</td>';
    dlgbody += '<td align="left"><input id="rqe_paredit_describib" type="text" value="'+cc.ds+'" size="40"></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td align="left" style="font-style: italic;">'+local_texts[14]+'</td>';
    dlgbody += '<td colspan="2"><pre id="rqe_paredit_subs"></pre></td>';
    dlgbody += '</tr>';

    dlgbody += '<tr>';
    dlgbody += '<td colspan="3"><button id="rqe_paredit_action_cancel" style="font-weight: bold; padding: 5px;">'+local_texts[7]+'</button>';
    if(cc.kw == '')
    {
        dlgbody += '<button id="rqe_paredit_action_insert" style="font-weight: bold; padding: 5px;">'+local_texts[17]+'</button>';
    }
    else
    {
        dlgbody += '<button id="rqe_paredit_action_save" style="font-weight: bold; padding: 5px;">'+local_texts[15]+'</button>';
        dlgbody += '<button id="rqe_paredit_action_delete" style="font-weight: bold; padding: 5px;">'+local_texts[16]+'</button>';
    }
    dlgbody += '</td></tr>';

    dlgbody += '</table></div>';

    prepare_ckdialog(local_texts[10],dlgbody);
    popup_ckdialog();

    jQuery('#rqe_paredit_typesel').change(function(e) { rqeditParameditUpdatePreview(); });
    jQuery('#rqe_paredit_keywordib').on('input',function(e) { rqeditParameditUpdatePreview(); });
    jQuery('#rqe_paredit_typeextra').on('input',function(e) { rqeditParameditUpdatePreview(); });
    jQuery('#rqe_paredit_describib').on('input',function(e) { rqeditParameditUpdatePreview(); });

    jQuery('#rqe_paredit_action_cancel').click(function(){
        close_ckdialog();
    });
    jQuery('#rqe_paredit_action_insert').click(function(){
        parstore.push({
            type: jQuery('#rqe_paredit_typesel').val(),
            keyw: jQuery('#rqe_paredit_keywordib').val(),
            descr: jQuery('#rqe_paredit_describib').val(),
            extra: jQuery('#rqe_paredit_typeextra').val() });
        rqeditParameditUpdateParbuttons();
        close_ckdialog();
    });
    jQuery('#rqe_paredit_action_save').click(function(){
        for(var i = 0;i < parstore.length;++i)
            if(parstore[i].keyw == pkeyw)
            {
                parstore[i].keyw = jQuery('#rqe_paredit_keywordib').val();
                parstore[i].type = jQuery('#rqe_paredit_typesel').val();
                parstore[i].descr = jQuery('#rqe_paredit_describib').val();
                parstore[i].extra = jQuery('#rqe_paredit_typeextra').val();
                break;
            }
        rqeditParameditUpdateParbuttons();
        close_ckdialog();
    });
    jQuery('#rqe_paredit_action_delete').click(function(){
        for(var i = 0;i < parstore.length;++i)
            if(parstore[i].keyw == pkeyw) {
                parstore.splice(i,1);
                break;
            }
        rqeditParameditUpdateParbuttons();
        close_ckdialog();
    });

    rqe_lasttype = '';
    rqeditParameditUpdatePreview();
    jQuery('#rqe_paredit_keywordib').focus();
}

function rqeditParameditUpdatePreview()
{
    var type = jQuery('#rqe_paredit_typesel').val();
    var keyw = jQuery('#rqe_paredit_keywordib').val();
    var mkeyw = keyw.replace(/[^0-9a-zA-Z_]/g,'');
    if(keyw != mkeyw)
        jQuery('#rqe_paredit_keywordib').val(keyw = mkeyw);

    var descr = jQuery('#rqe_paredit_describib').val();
    var mdescr = descr.replace(';','').replace(':','');
    if(descr != mdescr)
        jQuery('#rqe_paredit_describib').val(mdescr);

    var extra = jQuery('#rqe_paredit_typeextra').val();
    var mextra = extra.replace(';','').replace(':','');
    if(extra != mextra)
        jQuery('#rqe_paredit_typeextra').val(mextra);

    var subs = [];
    if(keyw != '')
    {
        subpatterns = partypeopts[type][2].split(',');
        for (var j = 0;j < subpatterns.length; ++j)
            subs.push('<button title="'+local_texts[18]+'" class="rqe_paredit_parsubs_insert">'+
                      subpatterns[j].replace('@', keyw)  +
                      '</button>');
    }
    jQuery('#rqe_paredit_subs').html(subs.join(','));

    if(rqe_lasttype != type)
    {
        jQuery('#rqe_paredit_typeextra').prop("disabled", ! partypeopts[type][0]);
        jQuery('#rqe_extraline_descr').html(partypeopts[type][1]);
        rqe_lasttype = type;
    }
}

function rqeditShowEditFullParameterstring()
{
    dlgbody = '';

    var f = jQuery('#rqe_parameteredit').val();

    dlgbody += '<center>';
    dlgbody += '<textarea style="width:90%;" id="rqe_paredit_fullpardef" rows="4">'+f+'</textarea>';
    dlgbody += '<br/><br/>';
    dlgbody += '<span>';
    dlgbody += '<button id="rqe_fullparedit_action_cancel" style="font-weight: bold; padding: 5px;">'+local_texts[7]+'</button>';
    dlgbody += '<button id="rqe_fullparedit_action_save" style="font-weight: bold; padding: 5px;">'+local_texts[15]+'</button>';
    dlgbody += '</span></center>';

    prepare_ckdialog(local_texts[19],dlgbody);
    popup_ckdialog();

    jQuery('#rqe_fullparedit_action_cancel').click(function(){
        close_ckdialog();
    });

    jQuery('#rqe_fullparedit_action_save').click(function(){
        jQuery('#rqe_parameteredit').val(jQuery('#rqe_paredit_fullpardef').val());
        rqeditParseParameterDefinitionString();
        rqeditParameditUpdateParbuttons();
        jQuery('#rqedit_save_button').addClass('rawqsqlchanged');
        close_ckdialog();
    });

    jQuery('#rqe_paredit_fullpardef').focus();
}

function rqeditChangeHeightOfEditArea(changewith)
{
    var h;
    var editormode = jQuery('#rq_editormode').val();
    if(editormode == 'm')
        h = jQuery('#sql_edit_monacobox').height();
    if(editormode == 't')
        h = jQuery('#sql_edit_input').height();
    h = h + changewith;
    if(h < 50)
        h = 50;
    if(editormode == 'm')
        jQuery('#sql_edit_monacobox').height(h);
    if(editormode == 't')
        jQuery('#sql_edit_input').height(h);
    localStorage.monacoedit_h=JSON.stringify(h);
}

function rqeditSetHeightOfEditArea(defaultheight)
{
    var h = defaultheight;
    try{
        h = JSON.parse(localStorage.monacoedit_h);
    } catch(ex){}
    if(typeof h === 'undefined')
    {
        h = defaultheight;
        localStorage.monacoedit_h=JSON.stringify(h);
    }
    return h;
}

function rqeditFireupMonacoEditor(pathofmodule,height)
{
    require.config({ paths: { 'vs': pathofmodule + '/monaco-editor/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        var sqltext = jQuery('#sql_edit_input').val();
        jQuery('#sql_edit_input').fadeOut('fast',function() {
            jQuery('#sql_edit_monacobox').fadeIn();
        }).attr('readonly',false);

        window.monaco_editor = monaco.editor.create(document.getElementById('sql_edit_monacobox'),{
            value: sqltext,
            language: 'sql',
            scrollbar: { vertical: 'auto',horizontal: 'auto'},
            automaticLayout: true,
            theme: 'vs-dark',
            tabSize: 4,
            insertSpaces: true,
        });

        jQuery('#sql_edit_monacobox').height(height);

        window.monaco_editor.onDidChangeModelContent(function (e) {
            jQuery('#rqedit_save_button').addClass('rawqsqlchanged');
        });
    });
}

function rawQEditorInsertAtCaret(areaId, text)
{
    var txtarea = document.getElementById(areaId);
    if (!txtarea) {
        return;
    }

    var scrollPos = txtarea.scrollTop;
    var strPos = 0;
    var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
        "ff" : (document.selection ? "ie" : false));
    if (br == "ie") {
        txtarea.focus();
        var range = document.selection.createRange();
        range.moveStart('character', -txtarea.value.length);
        strPos = range.text.length;
    } else if (br == "ff") {
        strPos = txtarea.selectionStart;
    }

    var front = (txtarea.value).substring(0, strPos);
    var back = (txtarea.value).substring(strPos, txtarea.value.length);
    txtarea.value = front + text + back;
    strPos = strPos + text.length;
    if (br == "ie") {
        txtarea.focus();
        var ieRange = document.selection.createRange();
        ieRange.moveStart('character', -txtarea.value.length);
        ieRange.moveStart('character', strPos);
        ieRange.moveEnd('character', 0);
        ieRange.select();
    } else if (br == "ff") {
        txtarea.selectionStart = strPos;
        txtarea.selectionEnd = strPos;
        txtarea.focus();
    }
    txtarea.scrollTop = scrollPos;
}

function insertToMonacoEditor(text)
{
    var selection = window.monaco_editor.getSelection();
    var range = new monaco.Range(selection.startLineNumber,selection.startColumn,selection.endLineNumber,selection.endColumn);
    var id = { major: 1, minor: 1 };
    var op = {identifier: id, range: range, text: text, forceMoveMarkers: true};
    window.monaco_editor.executeEdits("my-source", [op]);
    window.monaco_editor.focus();
}

function rq_getsql_str()
{
    if(jQuery('#rq_editormode').val() == 'm')
        jQuery('#sql_edit_input').val(window.monaco_editor.getValue());
}