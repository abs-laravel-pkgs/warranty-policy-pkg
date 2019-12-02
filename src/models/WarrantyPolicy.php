<?php

namespace Abs\WarrantyPolicyPkg;

use App\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyPolicy extends Model {
	protected $table = 'warranty_policies';
	use SoftDeletes;
	protected $fillable = [
		'company_id',
		'code',
		'name',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	public function warrantyPolicyDetails() {
		return $this->hasMany('Abs\WarrantyPolicyPkg\WarrantyPolicyDetail', 'warranty_policy_id')->orderBy('priority', 'asc');
	}

	public static function createFromCollection($records, $company = null, $specific_company = null, $tc) {
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->company_code) {
					continue;
				}

				if ($specific_company) {
					if ($record_data->company_code != $specific_company->code) {
						continue;
					}
				}

				if ($tc) {
					if ($record_data->tc != $tc) {
						continue;
					}
				}

				$record = self::createFromObject($record_data, $company);
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public static function createFromObject($record_data, $company = null) {
		$errors = [];
		if (!$company) {
			$company = Company::where('code', $record_data->company_code)->first();
		}
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company_code);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data->code,
		]);
		$record->name = $record_data->name;
		$record->created_by_id = $admin->id;
		if ($record_data->status != 1) {
			$record->deleted_at = date('Y-m-d');
		}
		$record->save();
	}

}
