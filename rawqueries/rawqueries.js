
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