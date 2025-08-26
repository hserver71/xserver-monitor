<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToVpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vps', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('ip')->nullable();
            $table->text('domains')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('linename')->nullable();
            $table->string('serverdomain')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vps', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'ip', 'domains', 'username', 'password', 
                'linename', 'serverdomain'
            ]);
        });
    }
} 