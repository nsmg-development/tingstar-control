<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_medias', function (Blueprint $table) {
            $table->bigInteger('article_id')->index()->comment('수집데이터 id');
            $table->string('type', 25)->comment('미디어 타입, image, movie...');
            $table->string('storage_url', 200)->nullable()->comment('저장된 미디어 url');
            $table->text('url')->comment('미디어 url');
            $table->unsignedInteger('width')->nullable()->comment('미디어 가로 사이즈(px)');
            $table->unsignedInteger('height')->nullable()->comment('미디어 세로 사이즈(px)');
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
        Schema::dropIfExists('article_medias');
    }
}
