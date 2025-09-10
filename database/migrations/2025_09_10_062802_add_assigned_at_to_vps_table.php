<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssignedAtToVpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vps', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('serverdomain');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vps', function (Blueprint $table) {
            $table->dropColumn('assigned_at');
        });
    }
}
