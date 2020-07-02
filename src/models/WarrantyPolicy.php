<?php

namespace Abs\WarrantyPolicyPkg;

use Abs\HelperPkg\Traits\SeederTrait;
// use Illuminate\Database\Eloquent\Model;
use App\BaseModel;
use App\Company;
use App\SerialNumberGroup;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyPolicy extends BaseModel {
	protected $table = 'warranty_policies';
	public static $AUTO_GENERATE_CODE = true;
	use SeederTrait;
	use SoftDeletes;
	protected $fillable = [
		'company_id',
		'code',
		'name',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	protected static $excelColumnRules = [
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
	];

	public function warrantyPolicyDetails() {
		return $this->hasMany('Abs\WarrantyPolicyPkg\WarrantyPolicyDetail', 'warranty_policy_id');
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
			$warrantyPolicyDetail = $this->warrantyPolicyDetails()->orderBy('priority', 'desc')->first();
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
			foreach ($this->warrantyPolicyDetails()->orderBy('priority', 'asc')->get() as $key => $warrantyPolicyDetail) {
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
						$response['warranty_policy_detail_id'] = -1;
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
			$response['warranty_policy_detail_id'] = -1;
		}
		return $response;
	}
	public function getWarrantyPolicyDetailView($battery_billed_date_format, $battery_billed_date) {
		$response = array();
		$warranty_details = [];
		//GET WARRANTY POLICY DETAILS
		if ($this->warrantyPolicyDetails) {
			foreach ($this->warrantyPolicyDetails()->orderBy('priority', 'asc')->get() as $key => $warrantyPolicyDetail) {
				//GET TOTAL BATTERY WARRANTY DAYS
				$total_battery_warranty_days = $this->getTotalBatteryWarrantyDays($battery_billed_date_format, $battery_billed_date, $warrantyPolicyDetail);
				//IF BATTERY USED DAYS EXCEED WARRANRY DAYS

				$warranty_details['more_info'][$key] = $warrantyPolicyDetail->more_info;
				if ($total_battery_warranty_days > 0) {
					$up_to_date = date('Y-m-d', strtotime("+" . intval($total_battery_warranty_days) . " day"));
					$warranty_details['warranty_policy_upto_date'][$key] = $up_to_date;
				} else {
					$up_to_date = date('Y-m-d', strtotime("-" . intval($total_battery_warranty_days) . " day"));
					$warranty_details['warranty_policy_upto_date'][$key] = $up_to_date;
				}
			}
		} else {
			$warranty_details['more_info'] = '';
			$warranty_details['warranty_policy_upto_date'] = '';
		}
		return $warranty_details;
	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		$errors = [];
		$company = Company::where('code', $record_data['Company Code'])->first();
		if (!$company) {
			return [
				'success' => false,
				'errors' => ['Invalid Company : ' . $record_data['Company Code']],
			];
		}

		if (!isset($record_data['created_by_id'])) {
			$admin = $company->admin();

			if (!$admin) {
				return [
					'success' => false,
					'errors' => ['Default Admin user not found'],
				];
			}
			$created_by_id = $admin->id;
		} else {
			$created_by_id = $record_data['created_by_id'];
		}

		if (Self::$AUTO_GENERATE_CODE) {
			if (empty($record_data['Code'])) {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'name' => $record_data['Name'],
				]);
				$result = SerialNumberGroup::generateNumber(static::$SERIAL_NUMBER_CATEGORY_ID);
				if ($result['success']) {
					$record_data['Code'] = $result['number'];
				} else {
					return [
						'success' => false,
						'errors' => $result['errors'],
					];
				}
			} else {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'code' => $record_data['Code'],
				]);
			}
		} else {
			$record = static::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
			]);
		}

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		$record->company_id = $company->id;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

	/*public static function createFromCollection($records, $company = null, $specific_company = null, $tc) {
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
	*/

}
