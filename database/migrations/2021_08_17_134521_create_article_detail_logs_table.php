<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleDetailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_detail_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id')->index()->comment('매체 id');
            $table->bigInteger('article_id');
            $table->string('user_id');
            $table->string('type', 20);
            $table->timestamps();

            $table->index(['article_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_detail_logs');
    }
}
