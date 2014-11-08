<?php namespace Devonzara\Warden;

use Illuminate\Support\Collection;

class Warden {

	/**
	 * An instance of the current guest/logged in user.
	 *
	 * @var mixed
	 */
	protected $currentUser;

	/**
	 * The pre-game show, it's not fun but it always happens.
	 */
	function __construct()
	{
		$this->setCurrentUser($this->getUser());
	}

	/**
	 * Static method for accessing our config.
	 *
	 * @param $key
	 * @return mixed
	 */
	public static function config($key)
	{
		return app('config')->get("warden::{$key}");
	}

	/**
	 * Return a Collection of Roles
	 *
	 * @return mixed
	 */
	public function getRolesCollection()
	{
		return $this->getUser()->roles;
	}

	/**
	 * Return an array of our roles.
	 *
	 * @return mixed
	 */
	public function getRoles()
	{
		return $this->getRolesCollection()->attributesToArray();
	}

	/**
	 * Determine if the current user belong to the specified role.
	 *
	 * @param $key
	 * @return mixed
	 */
	public function hasRole($key)
	{
		return $this->getUser()->hasRole($key);
	}

	/**
	 * Assign the User to the specified Role.
	 *
	 * @param   $role  mixed  Accepts a Role, key, or id.
	 * @return  mixed
	 */
	public function addRole($role)
	{
		return $this->getUser()->addRole($key);
	}

	/**
	 * Find or create a User instance to work with.
	 *
	 * @return mixed
	 */
	public function	getUser()
	{
		if (isset($this->currentUser)) return $this->currentUser;

		if (app('auth')->check()) return app('auth')->user();

		return $this->createFakeUser();
	}

	/**
	 * Set the current user.
	 *
	 * @param $user
	 */
	public function setCurrentUser($user)
	{
		$this->currentUser = $user;
	}

	/**
	 * Create an instance of User for our guest.
	 *
	 * @param string $guestRole
	 * @return mixed
	 */
	public function createFakeUser($guestRole = 'guest')
	{
		if ( ! $this->config('use_guest')) return $this->newUserInstance();

		$roles = call_user_func($this->config('role_model') .'::query')
			->whereKey($this->config('guest_role') ?: $guestRole)
			->with('permissions')
			->get();

		$user = $this->newUserInstance();
		$user->setAttribute('roles', $roles);

		return $user;
	}

	/**
	 * Create a new/blank instance of User, no questions asked.
	 *
	 * @return mixed
	 */
	public function newUserInstance()
	{
		$provider = app('auth')->getProvider();

		return $provider->createModel()->newInstance([], false);
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
		$user = $this->getUser();

		return $user->$method($parameters);
	}

}
