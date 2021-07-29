<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleOwnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_owners', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('platform', 25)->comment('수집 대상: instagram, facebook, youtube, tiktok 등');
            $table->string('url')->nullable()->comment('소유자 페이지 URL');
            $table->string('name')->nullable()->comment('소유자 이름, 채널명');
            $table->string('storage_thumbnail_url', 200)->nullable()->comment('저장된 썸네일 url');
            $table->text('thumbnail_url')->nullable()->comment('썸네일 URL');
            $table->unsignedInteger('thumbnail_width')->nullable()->comment('썸네일 가로 사이즈(px)');
            $table->unsignedInteger('thumbnail_height')->nullable()->comment('썸네일 세로 사이즈(px)');
            $table->unique(['id', 'platform']);
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
        Schema::dropIfExists('article_owners');
    }
}
