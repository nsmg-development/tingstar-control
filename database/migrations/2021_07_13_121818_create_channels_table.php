<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id')->comment('매체 id');
            $table->string('platform', 25)->comment('수집 대상: instagram, facebook, youtube, tiktok 등');
            $table->string('name')->comment('계정이름');
            $table->string('channel')->comment('계정정보(채널)');
            $table->boolean('state')->default(false)->comment('사용 여부(0:미사용, 1:사용)');
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
        Schema::dropIfExists('channels');
    }
}
