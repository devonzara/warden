<?php namespace Devonzara\Warden;

use Devonzara\Warden\Exceptions\RoleAlreadyAssignedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait WardenTrait {

	/**
	 * Cached list of permissions.
	 *
	 * @var array $warden
	 */
	protected $warden;

	/**
	 * An array to map the magic method calls.
	 *
	 * @var array
	 */
	protected $magicPrefixes = [
		'may' => 'may',
		'is'  => 'hasRole'
	];

	/**
	 * A User may belong to many Roles.
	 *
	 * @return mixed
	 */
	public function roles()
	{
		$relation = $this->belongsToMany(Warden::config('role_model'));

		return $relation->with('permissions')->withTimestamps();
	}

	/**
	 * A User may morph to many Permissions.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
	 */
	public function permissions()
	{
		$relation = $this->morphToMany(
			Warden::config('permission_model'),
			Warden::config('permission_type_name'),
			Warden::config('permission_pivot_table')
		);

		return $relation->withPivot('value')->withTimestamps();
	}

	/**
	 * Determine if the user belongs to the specified role.
	 *
	 * @param $role
	 * @return bool
	 */
	public function hasRole($role)
	{
		return $this->in_arrayi($role, $this->getRolesList());
	}

	/**
	 * Determine if the user has the specified permission.
	 *
	 * @param $key
	 * @return bool
	 */
	public function may($key)
	{
		$key = ! is_array($key) ?: $key[0];

		foreach ($this->getPermissionsList() as $permission)
		{
			if ( ! strcasecmp($permission['key'], $key)) return true;
		}

		return false;
	}

	/**
	 * Assign the User to the specified Role.
	 *
	 * @param   $role  mixed  Accepts a Role, key, or id.
	 * @return  void
	 * @throws  \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function addRole($role)
	{
		if ( ! $this->exists)
		{
			throw (new ModelNotFoundException)->setModel(get_class($this));
		}

		if ($role instanceof Model)
		{
			$this->addRoleFromModel($role);
		}
		else
		{
			$this->addRoleFromColumn($role);
		}
	}

	/**
	 * Assign the current user to the specified Role model.
	 *
	 * @param $role
	 * @return void
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 * @throws \Devonzara\Warden\Exceptions\RoleAlreadyAssignedException
	 */
	protected function addRoleFromModel($role)
	{
		if ( ! $role->exists)
		{
			throw (new ModelNotFoundException)->setModel(get_class($role));
		}

		if ($this->roles->contains($role->id))
		{
			throw (new RoleAlreadyAssignedException)->setModels($this, $role);
		}

		$this->saveRole($role->getKey());
	}

	/**
	 * Find a matching Role and assign the current user to it.
	 *
	 * @param $value
	 * @return void
	 */
	protected function addRoleFromColumn($value)
	{
		$column = is_string($value) ? 'key' : 'id';

		$model = Warden::config('role_model');
		$role = new $model;

		$role = $role->where($column, $value)->firstOrFail();

		$this->addRoleFromModel($role);
	}

	/**
	 * Assign the User to the specified Role id.
	 *
	 * @param $id
	 * @return void
	 */
	protected function saveRole($id)
	{
		// Attach the Role to the User.
		$this->roles()->attach($id);

		// Update the collection of Roles.
		$this->load('roles');
	}

	/**
	 * Parse the roles/permissions into easy to access arrays.
	 * This will allow us to reduce the amount of overhead
	 * each call and allow us to simplify other methods.
	 *
	 * @return void
	 */
	protected function buildPermissionsCache()
	{
		if (isset($this->warden)) return;

		$this->parseRoles();

		sort($this->warden['roles']);
		sort($this->warden['permissions']);
	}

	/**
	 * Create an array of all roles linked to this user.
	 *
	 * @return bool
	 */
	protected function parseRoles()
	{
		$this->warden['roles'] = $this->warden['permissions'] = [];

		foreach ($this->roles as $role)
		{
			$this->cacheRole($role->key);
			$this->parsePermissions(
				$role->key, $role->permissions, $role->weight
			);
		}
	}

	/**
	 * Create an array of all permissions for this user.
	 *
	 * @param $permissions
	 * @param $weight
	 */
	protected function parsePermissions($role, $permissions, $weight)
	{
		// Parse role-based permissions.
		foreach ($permissions as $permission)
		{
			if ($this->shouldOverride($permission->key, $weight))
			{
				$this->cachePermission(
					$permission->key,
					$permission->name,
					$permission->pivot->value,
					$weight,
					$role
				);
			}
		}

		// Parse user-level permissions.
		foreach ($this->permissions as $permission)
		{
			$this->cachePermission(
				$permission->key, $permission->name, $permission->pivot->value, -1, 'User'
			);
		}
	}

	/**
	 * Determine if the given permission should override an existing one.
	 *
	 * @param $key
	 * @param $weight
	 * @return bool
	 */
	protected function shouldOverride($key, $weight)
	{
		$currentWeight = $this->getPermissionWeight($key);

		if ($currentWeight != -1 && $currentWeight < $weight) return true;

		return false;
	}

	/**
	 * Getter method for the rolesList array.
	 *
	 * @return mixed
	 */
	public function getRolesList($checkCache = true)
	{
		if ($checkCache) $this->buildPermissionsCache();

		return $this->warden['roles'];
	}

	/**
	 * Getter method for the wardenList array.
	 *
	 * @return mixed
	 */
	public function getPermissionsList($checkCache = true)
	{
		if ($checkCache) $this->buildPermissionsCache();

		return $this->warden['permissions'];
	}

	/**
	 * Get the specified permission from the cache from it's key.
	 *
	 * @param      $key
	 * @param bool $checkCache
	 * @return bool
	 */
	protected function getPermission($key, $checkCache = true)
	{
		if ($checkCache) $this->buildPermissionsCache();

		return isset($this->warden['permissions'][$key]) ?: false;
	}

	/**
	 * Get the current weight for the given permission key.
	 *
	 * @param $key
	 * @return int
	 */
	protected function getPermissionWeight($key)
	{
		$permission = $this->getPermission($key);

		return $permission ? $permission['weight'] : 0;
	}

	/**
	 * Cache a role.
	 *
	 * @param $key
	 */
	protected function cacheRole($key)
	{
		$this->warden['roles'][$key] = $key;
	}

	/**
	 * Cache the given permission.
	 *
	 * @param $key
	 * @param $name
	 * @param $value
	 * @param $weight
	 */
	protected function cachePermission($key, $name, $value, $weight, $source)
	{
		$blah = null;
		$this->warden['permissions'][$key] = [
			'key'    => $key,
			'name'   => $name,
			'value'  => $value,
			'weight' => $weight,
			'source' => $source
		];
	}

	/**
	 * Return the array of magic prefixes.
	 *
	 * @return array
	 */
	public function getMagicPrefixes()
	{
		return $this->magicPrefixes;
	}

	/**
	 * Case insensitive in_array.
	 *
	 * @param       $needle
	 * @param array $haystack
	 * @param bool  $strict
	 * @return bool
	 */
	public function in_arrayi($needle, array $haystack, $strict = false)
	{
		return in_array(
			strtolower($needle), array_map('strtolower', $haystack), $strict
		);
	}

	/**
	 * This is the place where dreams come true... No, it's not Disney
	 * World. Here's where we're able to resolve magic method calls.
	 * Examples: isModerator(), isOwner(), and mayAccessAdmin().
	 *
	 * @param $method
	 * @param $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		// Resolve magic method calls.
		foreach ($this->getMagicPrefixes() as $prefix => $forward)
		{
			if (starts_with($method, $prefix))
			{
				return $this->$forward(
					snake_case(substr($method, strlen($prefix)))
				);
			}
		}

		// Prefix not found, bubble the call up the chain.
		return parent::__call($method, $parameters);
	}

}
