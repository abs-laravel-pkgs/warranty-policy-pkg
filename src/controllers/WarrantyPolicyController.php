<?php

namespace Abs\WarrantyPolicyPkg;
use Abs\WarrantyPolicyPkg\WarrantyPolicy;
use Abs\WarrantyPolicyPkg\WarrantyPolicyDetail;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class WarrantyPolicyController extends Controller {

	public function __construct() {
	}

	public function getWarrantyPolicyList() {
		$warranty_policy_list = WarrantyPolicy::withTrashed()
			->select(
				'warranty_policies.id',
				'warranty_policies.code',
				'warranty_policies.name',
				DB::raw('IF(warranty_policies.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('warranty_policies.company_id', Auth::user()->company_id)
			->orderby('warranty_policies.id', 'desc');

		return Datatables::of($warranty_policy_list)
			->addColumn('action', function ($warranty_policy_list) {
				$edit = asset('public/img/content/table/edit-yellow.svg');
				$edit_active = asset('public/img/content/table/edit-yellow-active.svg');
				$delete = asset('/public/img/content/table/delete-default.svg');
				$delete_active = asset('/public/img/content/table/delete-active.svg');
				return '
					<a href="#!/warranty-policy-pkg/warranty-policy/edit/' . $warranty_policy_list->id . '" title="Edit" dusk="edit-btn"><img src="' . $edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $edit_active . '" onmouseout=this.src="' . $edit . '" >
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_warranty_policy"
					onclick="angular.element(this).scope().deleteWarrantyPolicy(' . $warranty_policy_list->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
					</a>
					';
			})
			->make(true);
	}

	public function getWarrantyPolicyFormData($id = NULL) {
		if (!$id) {
			$warranty_policy = new WarrantyPolicy;
			$warranty_policy_details = new WarrantyPolicyDetail;
			$action = 'Add';
		} else {
			$warranty_policy = WarrantyPolicy::withTrashed()->find($id);
			$warranty_policy_details = WarrantyPolicyDetail::where('warranty_policy_id', $id)->get();
			$action = 'Edit';
		}
		$this->data['warranty_type_list'] = Config::getWarrantyType();
		$this->data['duration_type_list'] = Config::getWarrantyDurationType();
		$this->data['warranty_policy'] = $warranty_policy;
		$this->data['warranty_policy_details'] = $warranty_policy_details;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveWarrantyPolicy(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Policy Code is Required',
				'code.max' => 'Maximum 191 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Policy Code is Already taken',
				'name.required' => 'Policy Code is Required',
				'name.max' => 'Maximum 191 Characters',
				'name.min' => 'Minimum 3 Characters',
				'name.unique' => 'Policy name is Already taken',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required',
					'max:255',
					'min:3',
					'unique:warranty_policies,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required',
					'max:255',
					'min:3',
					'unique:warranty_policies,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			$warranty_policy_id=$request->id;
			if (!empty($request->policy_details)) {
				//dd($request->policy_details);
				$array_data = array_column($request->policy_details, 'priority');
				$array_data_unique = array_unique($array_data);
				if (count($array_data) != count($array_data_unique)) {
					return response()->json(['success' => false, 'errors' => ['Priority must be a unique values']]);
				}
				foreach ($request->policy_details as $policy_detail) {
					$error_messages1=[
						'priority.unique' =>'Priority must be a unique'
					];
					//dd($policy_detail,$warranty_policy_id);
					$validator = Validator::make($policy_detail, [
						'warranty_type_id' => 'required',
						'duration' => 'required',
						'duration_type_id' => 'required',
						'more_info' => 'nullable|max:255',
						'priority'=>[
							'unique:warranty_policy_details,priority,'. $policy_detail['id'] .',id,warranty_policy_id,' . $warranty_policy_id,
						]
					],$error_messages1);
					if ($validator->fails()) {
						return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
					}
				}
			}

			DB::beginTransaction();
			if (!empty($request->policy_detail_removal_id)) {
				$removal_id = json_decode($request->policy_detail_removal_id);
				$policy_details_remove = WarrantyPolicyDetail::whereIn('id', $removal_id)->delete();
			}

			if (!$request->id) {
				$warranty_policy = new WarrantyPolicy;
				$warranty_policy->created_by_id = Auth::user()->id;
				$warranty_policy->created_at = Carbon::now();
			} else {
				$warranty_policy = WarrantyPolicy::withTrashed()->find($request->id);
				$warranty_policy->updated_by_id = Auth::user()->id;
				$warranty_policy->updated_at = Carbon::now();
			}
			$warranty_policy->fill($request->all());
			$warranty_policy->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$warranty_policy->deleted_by_id = Auth::user()->id;
				$warranty_policy->deleted_at = Carbon::now();
			} else {
				$warranty_policy->deleted_by_id = NULL;
				$warranty_policy->deleted_at = NULL;
			}
			$warranty_policy->save();

			if (!empty($request->policy_details)) {
				foreach ($request->policy_details as $policy_details) {
					$warranty_policy_details = WarrantyPolicyDetail::firstOrNew(['id' => $policy_details['id']]);
					$warranty_policy_details->warranty_policy_id = $warranty_policy->id;
					$warranty_policy_details->fill($policy_details);
					$warranty_policy_details->save();
				}
			}

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Warranty Policy Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Warranty Policy Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteWarrantyPolicy($id) {
		$delete_status = WarrantyPolicy::where('id', $id)->forceDelete();
		if ($delete_status) {
			return response()->json(['success' => true]);
		}
	}
}
