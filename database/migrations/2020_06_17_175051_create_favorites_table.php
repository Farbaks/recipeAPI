<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('commentId')->unsigned()->nullable();
            $table->integer('recipeId')->unsigned()->nullable();
            $table->integer('userId')->unsigned();
            $table->timestamps();

            $table->foreign('commentId')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('recipeId')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
