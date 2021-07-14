<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_id')->comment('플랫폼 id');
            $table->string('login_id')->comment('로그인 아이디');
            $table->string('login_password')->comment('로그인 비밀번호');
            $table->string('token')->nullable()->comment('인증토큰');
            $table->string('user_id')->nullable()->comment('플랫폼 계정 사용자 고유값');
            $table->boolean('state')->default(true)->comment('계정 사용 가능 여부(0:불가, 1:가능)');
            $table->unsignedInteger('use_count')->default(0)->comment('사용 횟수');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_accounts');
    }
}
