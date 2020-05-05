<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WarrantyPolicyDetailsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('warranty_policy_details')) {
			Schema::create('warranty_policy_details', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('warranty_policy_id');
				$table->unsignedInteger('warranty_type_id');
				$table->unsignedInteger('duration_type_id');
				$table->unsignedDecimal('duration', 8, 2);
				$table->string('more_info', 255)->nullable();
				$table->unsignedTinyInteger('priority');

				$table->foreign('warranty_policy_id')->references('id')->on('warranty_policies')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('warranty_type_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('duration_type_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('warranty_policy_details');
	}
}
