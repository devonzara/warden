<?php namespace Devonzara\Warden\Entities;

use Devonzara\Warden\Warden;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model {

	/**
	 * A Permission may be morphed to many Roles.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
	 */
	public function roles()
	{
		$relation= $this->morphedToMany(
			Warden::config('role_model'),
			Warden::config('permission_type_name'),
			Warden::config('permission_pivot_table')
		);

		return $relation->withPivot('value');
	}

}
