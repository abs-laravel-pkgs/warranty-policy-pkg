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

	public function getDaysBetweenTwoDates($date1, $date2) {
		$datetime1 = date_create($date1);
		$datetime2 = date_create($date2);
		$interval = date_diff($datetime1, $datetime2);
		return $interval->days;
	}

	public function getWarrantyPolicyEndDate($start_date) {
		$response = array();
		//GET WARRANTY POLICY DETAILS
		if ($this->warrantyPolicyDetails) {
			$total_battery_warranty_days = 0;
			$warrantyPolicyDetail = $this->warrantyPolicyDetails()->first();
			if (!$warrantyPolicyDetail) {
				$response['success'] = false;
				$response['end_date'] = '';
				$response['error'] = 'No policy found';
			} else {
				if ($warrantyPolicyDetail->duration_type_id == 7260) {
					$total_battery_warranty_days = intval($warrantyPolicyDetail->duration);
					$warranty_period_end_date = date('d-m-Y', strtotime($start_date . ' + ' . $total_battery_warranty_days . ' days'));
				} elseif ($warrantyPolicyDetail->duration_type_id == 7261) {
					//DURATION TYPE WEEKS
					$total_battery_warranty_days = 7 * intval($warrantyPolicyDetail->duration);
					$warranty_period_end_date = date('d-m-Y', strtotime($start_date . ' + ' . $total_battery_warranty_days . ' days'));
				} elseif ($warrantyPolicyDetail->duration_type_id == 7262) {
					//DURATION TYPE MONTHS
					$warranty_period_end_date = date('d-m-Y', strtotime("+" . intval($warrantyPolicyDetail->duration) . " months", strtotime($start_date)));
				} elseif ($warrantyPolicyDetail->duration_type_id == 7263) {
					//DURATION TYPE YEARS
					$warranty_period_end_date = date('d-m-Y', strtotime("+" . intval($warrantyPolicyDetail->duration) . " year", strtotime($start_date)));
				}
				$response['success'] = true;
				$response['end_date'] = $warranty_period_end_date;
			}
		} else {
			$response['success'] = false;
			$response['end_date'] = '';
			$response['error'] = 'No policy found';
		}
		return $response;
	}
	public function getTotalBatteryWarrantyDays($battery_billed_date_format, $battery_billed_date, $warrantyPolicyDetail) {
		$total_battery_warranty_days = 0;
		//DURATION TYPE DAYS
		if ($warrantyPolicyDetail->duration_type_id == 7260) {
			$total_battery_warranty_days = intval($warrantyPolicyDetail->duration);
		} elseif ($warrantyPolicyDetail->duration_type_id == 7261) {
			//DURATION TYPE WEEKS
			$total_battery_warranty_days = 7 * intval($warrantyPolicyDetail->duration);
		} elseif ($warrantyPolicyDetail->duration_type_id == 7262) {
			//DURATION TYPE MONTHS
			$warranty_period_end_date = date('d-m-Y', strtotime("+" . intval($warrantyPolicyDetail->duration) . " months", strtotime($battery_billed_date_format)));
			$total_battery_warranty_days = $this->getDaysBetweenTwoDates($battery_billed_date, $warranty_period_end_date);
		} elseif ($warrantyPolicyDetail->duration_type_id == 7263) {
			//DURATION TYPE YEARS
			$warranty_period_end_date = date('d-m-Y', strtotime("+" . intval($warrantyPolicyDetail->duration) . " year", strtotime($battery_billed_date_format)));
			$total_battery_warranty_days = $this->getDaysBetweenTwoDates($battery_billed_date, $warranty_period_end_date);
		}
		return $total_battery_warranty_days;

	}
	public function getWarrantyPolicyDetailStatus($battery_billed_date_format, $battery_billed_date, $battery_used_days) {
		$response = array();
		//GET WARRANTY POLICY DETAILS
		if ($this->warrantyPolicyDetails) {
			foreach ($this->warrantyPolicyDetails as $key => $warrantyPolicyDetail) {
				//GET TOTAL BATTERY WARRANTY DAYS
				$total_battery_warranty_days = $this->getTotalBatteryWarrantyDays($battery_billed_date_format, $battery_billed_date, $warrantyPolicyDetail);
				//IF BATTERY USED DAYS EXCEED WARRANRY DAYS
				if ($battery_used_days > $total_battery_warranty_days) {
					//IF NEXT WARRANTY POLICY DETAIL EXIST
					$next_key = $key + 1;
					if (isset($this->warrantyPolicyDetails[$next_key])) {
						continue;
					} else {
						$response['battery_status'] = 'Warranty expired';
						$response['warranty_policy_detail_id'] = $warrantyPolicyDetail->id;
					}
				} else {
					//FREE REPLACEMENT
					if ($warrantyPolicyDetail->warranty_type_id == 7360) {
						$response['battery_status'] = 'Free Replacement';
						$response['warranty_policy_detail_id'] = $warrantyPolicyDetail->id;
					} else {
						$response['battery_status'] = $warrantyPolicyDetail->more_info;
						$response['warranty_policy_detail_id'] = $warrantyPolicyDetail->id;
					}
					break;
				}
			}
		} else {
			$response['battery_status'] = 'No policy found';
			$response['warranty_policy_detail_id'] = '';
		}
		return $response;
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
