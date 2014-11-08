<?php namespace Devonzara\Warden\Exceptions;

class RoleAlreadyAssignedException extends \RuntimeException {

	/**
	 * The affected User model.
	 *
	 * @var Model
	 */
	protected $user;

	/**
	 * The Role model that was being assigned.
	 *
	 * @var Model
	 */
	protected $role;

	/**
	 * Set the affected User and Role model.
	 *
	 * @param  Model  $user
	 * @param  Model  $role
	 * @return $this
	 */
	public function setModels($user, $role)
	{
		$this->user = $user;
		$this->role = $role;

		$this->message = "User [{$user->id}] already belongs to Role [{$role->key}].";

		return $this;
	}

	/**
	 * Get the affected User model.
	 *
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Get the Role model that was being assigned.
	 *
	 * @return string
	 */
	public function getRole()
	{
		return $this->role;
	}

}
