
function insertToMyCurrentEditor(text)
{
    if(text == '')
        return;

    var em = jQuery('#rq_editormode').val();
    if(em == 't')
        rawQEditorInsertAtCaret('sql_edit_input','#' + text);
    if(em == 'm')
        insertToMonacoEditor('#' + text);

    jQuery('#rqedit_save_button').addClass('rawqsqlchanged');
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