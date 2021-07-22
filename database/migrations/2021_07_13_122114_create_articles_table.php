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
            $table->string('type', 25)->comment('수집 타입: keyword, channel');
            $table->string('keyword')->nullable()->comment('수집 키워드');
            $table->string('channel')->nullable()->comment('수집 채널');
            $table->string('url', 500)->comment('원본 url');
            $table->string('title')->comment('제목');
            $table->text('contents')->comment('내용');
            $table->text('thumbnail_url')->nullable()->comment('썸네일 url');
            $table->text('thumbnail_width')->nullable()->comment('썸네일 가로 사이즈(px)');
            $table->text('thumbnail_height')->nullable()->comment('썸네일 세로 사이즈(px)');
            $table->text('hashtag')->nullable()->comment('해시태그');
            $table->boolean('state')->default(false)->comment('노출 여부(0:비노출, 1:노출)');
            $table->datetime('date')->comment('게시일자');
            $table->unique(['media_id', 'url']);
            $table->index('media_id');
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
