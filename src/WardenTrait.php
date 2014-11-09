<?php namespace Devonzara\Warden;

use Devonzara\Warden\Exceptions\RoleAlreadyAssignedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait WardenTrait {

	/**
	 * Cached list of the evaluated permissions.
	 *
	 * @var array $permissionsCache
	 */
	protected $permissionsCache;

	/**
	 * An array to map the magic method calls.
	 *
	 * @var array
	 */
	protected $magicPrefixes = [
		'mayNot' => 'mayNot',
		'may'    => 'may',
		'is'     => 'hasRole'
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
	 * @param        $role
	 * @param string $column
	 * @return bool
	 */
	public function hasRole($role, $column = 'key')
	{
		if ($role instanceof Model) $role = $role->$column;

		foreach ($this->roles as $item)
		{
			if (! strcasecmp($item->$column, $role)) return true;
		}

		return false;
	}

	/**
	 * Determine if the user may perform the specified action.
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
	 * Determine if the user may not perform the specified action.
	 *
	 * @param $key
	 * @return bool
	 */
	public function mayNot($key)
	{
		return ! $this->may($key);
	}

	/**
	 * Assign the User to the specified Role.
	 *
	 * @param mixed $role   Accepts a Role, key, or id.
	 * @param bool  $reload To reload the model after adding the role or not.
	 * @return  void
	 */
	public function addRole($role, $reload = true)
	{
		if ( ! $this->exists)
		{
			throw (new ModelNotFoundException)->setModel(get_class($this));
		}

		if ($role instanceof Model)
		{
			$this->addRoleFromModel($role, $reload);
		}
		else
		{
			$this->addRoleFromColumn($role, $reload);
		}
	}

	/**
	 * Assign the current user to the specified Role model.
	 *
	 * @param $role
	 * @param $reload
	 * @return void
	 */
	protected function addRoleFromModel($role, $reload)
	{
		if ( ! $role->exists)
		{
			throw (new ModelNotFoundException)->setModel(get_class($role));
		}

		if ($this->roles->contains($role->id))
		{
			throw (new RoleAlreadyAssignedException)->setModels($this, $role);
		}

		$this->saveRole($role->getKey(), $reload);
	}

	/**
	 * Find a matching Role and assign the current user to it.
	 *
	 * @param $value
	 * @param $reload
	 * @return void
	 */
	protected function addRoleFromColumn($value, $reload)
	{
		$column = is_string($value) ? 'key' : 'id';

		$model = Warden::config('role_model');
		$role = new $model;

		$role = $role->where($column, $value)->firstOrFail();

		$this->addRoleFromModel($role, $reload);
	}

	/**
	 * Assign the User to the specified Role id.
	 *
	 * @param $id
	 * @param $reload
	 * @return void
	 */
	protected function saveRole($id, $reload)
	{
		// Attach the Role to the User.
		$this->roles()->attach($id);

		// Update the collection of Roles.
		if ($reload) $this->load('roles');
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
		if (isset($this->permissionsCache)) return;

		$this->permissionsCache = [];

		foreach ($this->roles as $role)
		{
			$this->parsePermissions(
				$role->key, $role->permissions, $role->weight
			);
		}

		sort($this->permissionsCache);
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
	 * Return an array of our roles.
	 *
	 * @return mixed
	 */
	public function getRoles()
	{
		return $this->roles;
	}

	/**
	 * Getter method for the wardenList array.
	 *
	 * @return mixed
	 */
	public function getPermissionsList($checkCache = true)
	{
		if ($checkCache) $this->buildPermissionsCache();

		return $this->permissionsCache;
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

		return isset($this->permissionsCache[$key]) ?: false;
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
		$this->permissionsCache[$key] = [
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
	 * This is the place where dreams come true... No, it's not Disney
	 * World. Here's where we're able to resolve magic method calls.
	 * Examples: isOwner(), mayAccessAdmin(), mayNotAccessSite()
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
			$startsWithUpper = ctype_upper(substr($method, strlen($prefix), 1));

			if ($startsWithUpper && starts_with($method, $prefix))
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
