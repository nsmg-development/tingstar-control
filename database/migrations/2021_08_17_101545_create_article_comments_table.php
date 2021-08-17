<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('user_name')->comment('작성자명');
            $table->unsignedBigInteger('article_comment_id')->nullable()->comment('댓글 id');
            $table->string('comment')->comment('댓글 내용');
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
        Schema::dropIfExists('article_comments');
    }
}
