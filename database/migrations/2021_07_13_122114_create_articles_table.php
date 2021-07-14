<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id')->comment('매체 id');
            $table->string('platform', 25)->comment('수집 대상: instagram, facebook, youtube, tiktok 등');
            $table->string('keyword')->nullable()->comment('수집 키워드');
            $table->string('url')->comment('원본 url');
            $table->string('title')->comment('제목');
            $table->string('contents')->comment('내용');
            $table->string('thumbnail_url')->nullable()->comment('썸네일 url');
            $table->string('hashtag')->nullable()->comment('해시태그');
            $table->boolean('state')->default(false)->comment('노출 여부(0:비노출, 1:노출)');
            $table->datetime('date')->comment('게시일자');
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
        Schema::dropIfExists('articles');
    }
}
