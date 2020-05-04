<?php
namespace Abs\WarrantyPolicyPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class WarrantyPolicyPkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//Warranty Policies
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'warranty-policies',
				'display_name' => 'Warranty Policies',
			],
			[
				'display_order' => 1,
				'parent' => 'warranty-policies',
				'name' => 'add-warranty-policy',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'warranty-policies',
				'name' => 'edit-warranty-policy',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'warranty-policies',
				'name' => 'delete-warranty-policy',
				'display_name' => 'Delete',
			],

		];

		Permission::createFromArrays($permissions);
	}
}