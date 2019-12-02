<?php

namespace Abs\WarrantyPolicyPkg;

use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;

class WarrantyPolicyDetail extends Model {
	protected $table = 'warranty_policy_details';
	public $timestamps = false;
	protected $fillable = [
		'warranty_policy_id',
		'warranty_type_id',
		'duration_type_id',
		'duration',
		'more_info',
		'priority',
	];

	public function warrantyPolicy() {
		return $this->belongsTo('Abs\WarrantyPolicyPkg\WarrantyPolicy', 'warranty_policy_id', 'id');
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

		$wp = WarrantyPolicy::where('code', $record_data->warranty_policy_code)->where('company_id', $company->id)->first();
		if (!$wp) {
			$errors[] = 'Invalid warranty_policy_code : ' . $record_data->warranty_policy_code;
		}

		$warranty_type = Config::where('name', $record_data->warranty_type)->where('config_type_id', 7015)->first();
		if (!$warranty_type) {
			$errors[] = 'Invalid warranty_type : ' . $record_data->warranty_type;
		}

		$duration_type = Config::where('name', $record_data->duration_type)->where('config_type_id', 7011)->first();
		if (!$duration_type) {
			$errors[] = 'Invalid duration_type : ' . $record_data->duration_type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'warranty_policy_id' => $wp->id,
			'warranty_type_id' => $warranty_type->id,
			'duration_type_id' => $duration_type->id,
			'duration' => $record_data->duration,
		]);
		$record->more_info = $record_data->more_info;
		$record->priority = $record_data->priority;
		$record->save();
	}

}
