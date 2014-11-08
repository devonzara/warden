<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Role Object Mode
	|--------------------------------------------------------------------------
	|
	| Here you can set the fully qualified class name to the model object
	| that will be used for roles. You can use the default one or set
	| your own and the provided class as a template for your model.
	|
	*/

	'role_model' => '\Devonzara\Warden\Entities\Role',

	/*
	|--------------------------------------------------------------------------
	| Permission Object Model
	|--------------------------------------------------------------------------
	|
	| Similar to the Role Object Model, the is the fully qualified class name
	| to the model object that will be used for permissions. Again, you're
	| able to implement your own model and reference it here to be used.
	|
	*/

	'permission_model' => '\Devonzara\Warden\Entities\Permission',

	/*
	|--------------------------------------------------------------------------
	| Database: Permission Type Name
	|--------------------------------------------------------------------------
	|
	| //
	| //
	| //
	|
	*/

	'permission_type_name' => 'entity',

	/*
	|--------------------------------------------------------------------------
	| Database: Permissions Pivot Table
	|--------------------------------------------------------------------------
	|
	| //
	| //
	| //
	|
	*/

	'permission_pivot_table' => 'permission_entities',

	/*
	|--------------------------------------------------------------------------
	| Enable Use Guest
	|--------------------------------------------------------------------------
	|
	| //
	| //
	| //
	|
	*/

	'use_guest' => true,

	/*
	|--------------------------------------------------------------------------
	| Guest Assigned Role
	|--------------------------------------------------------------------------
	|
	| //
	| //
	| //
	|
	*/

	'guest_role' => 'guest',

];
