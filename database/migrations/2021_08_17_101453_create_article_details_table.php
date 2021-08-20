<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('article_id')->index()->comment('수집된 자료 id');
            $table->unsignedInteger('like')->nullable()->default(0)->comment('좋아요 수');
            $table->unsignedInteger('dislike')->nullable()->default(0)->comment('싫어요 수');
            $table->unsignedInteger('report')->nullable()->default(0)->comment('신고 수');
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
        Schema::dropIfExists('article_details');
    }
}
