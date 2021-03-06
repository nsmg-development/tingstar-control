<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->bigInteger('id')->index();
            $table->unsignedBigInteger('media_id')->index()->comment('매체 id');
            $table->string('platform', 25)->index()->comment('수집 대상: instagram, facebook, youtube, tiktok 등');
            $table->string('type', 25)->comment('수집 타입: keyword, channel');
            $table->string('keyword')->nullable()->comment('수집 키워드');
            $table->string('channel')->nullable()->comment('수집 채널');
            $table->string('article_owner_id')->index()->nullable()->comment('게시자 아이디');
            $table->string('url', 500)->comment('원본 url');
            $table->string('title')->comment('제목');
            $table->text('contents')->comment('내용');
            $table->string('storage_thumbnail_url', 200)->nullable()->comment('저장된 썸네일 url');
            $table->text('thumbnail_url')->nullable()->comment('썸네일 url');
            $table->unsignedInteger('thumbnail_width')->nullable()->comment('썸네일 가로 사이즈(px)');
            $table->unsignedInteger('thumbnail_height')->nullable()->comment('썸네일 세로 사이즈(px)');
            $table->text('hashtag')->nullable()->comment('해시태그');
            $table->tinyInteger('state')->default(false)->comment('노출 여부(0:수집상태, 1:노출, 2:비노출)');
            $table->datetime('date')->index()->comment('게시일자');
            $table->boolean('has_media')->index()->default(false)->comment('이미지/동영상 존재 유무');
            $table->timestamps();

            $table->primary(['media_id', 'url']);
            $table->index(['state', 'media_id']);
        });

        DB::statement('ALTER TABLE articles ADD FULLTEXT INDEX search_contents_hashtag_index(title, contents, hashtag) WITH PARSER ngram');
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
