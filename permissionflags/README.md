# Permission flags module
This module creates permission flags to authenticated users according to an associative array of tag names and descriptions.
The flags are editable within the user add/edit page. The flags will always loaded and located in $user object.
The permission_hasflag($flag) function is also available for query a flag status.

Sample to define tags in _settings.php

	...
	global $user_permission_flags;
	$user_permission_flags = [
	    'perm_createsome'  => 'Permission to create something',
	    'perm_editsome'    => 'Permission to edit something',
	    'perm_createother' => 'Permission to create other things',
	    'perm_editother'   => 'Permission to edit other things',
	];
	...
