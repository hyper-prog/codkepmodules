# HttpSqlConn module

This module gives you the possibility to receive and handle json encapsulated sql commands through HTTP/POST requests.
There is a C++ json builder in gSAFE package which works together this module.

On C++ side you can do similar queries than CodKep:

    #include <builder.h> //From gSAFE

    ...

    HSqlBuilder b =  db_query("usertable")
                       .get("name")
                       .get("birthdate")
                       .get("comm")
                       .join_ffe("account","","usertable","uid","account","connuid")
                       .get("account","balance")
                       .cond_fv("age",Unquoted,"30",">");

    sendAsHttpPost("http://server/httpsqlconn/mysampleresource/secretreshash",b.json_string());

The HttpSqlConn module can process the query above check the permissions (set by hooks) and send back the required answer in Json

You can do the followig changes in _settings.php to enable the feature above:

    ...
    global $httpsqlconn;
    $httpsqlconn->define_routes = true;
    $httpsqlconn->resources = [
    'mysampleresource' => [
        'fastid' => 'secretreshash',
        'sqlreconnect' => false,
        // 'sql_user' => 'myuser',
        // 'sql_password' => 'secretpassword',
        // 'dataProvider' => 'customDataProvider',
        ],
    ];
