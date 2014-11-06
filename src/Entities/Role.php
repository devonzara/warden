<?php namespace Devonzara\Warden\Entities;

use Devonzara\Warden\Warden;
use Illuminate\Database\Eloquent\Model;

class Role extends Model {

	/**
	 * A Role may morph to many Permissions.
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

		return $relation->withPivot('value');
	}

}
