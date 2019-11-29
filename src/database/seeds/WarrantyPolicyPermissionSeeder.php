<?php
namespace Abs\WarrantyPolicyPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class WarrantyPolicyPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//WARRANTY POLICIES
			5000 => [
				'display_order' => 50,
				'parent_id' => null,
				'name' => 'warranty-policies',
				'display_name' => 'Warranty Policies',
			],
			5001 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'add-warranty-policy',
				'display_name' => 'Add',
			],
			5002 => [
				'display_order' => 2,
				'parent_id' => 5000,
				'name' => 'edit-warranty-policy',
				'display_name' => 'Edit',
			],
			5003 => [
				'display_order' => 3,
				'parent_id' => 5000,
				'name' => 'delete-warranty-policy',
				'display_name' => 'Delete',
			],

		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->save();
		}
	}
}