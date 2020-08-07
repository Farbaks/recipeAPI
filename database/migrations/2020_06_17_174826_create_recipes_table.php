<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('tags');
            $table->text('description');
            $table->string('duration');
            $table->string('difficulty');
            $table->string('pictureUrl')->nullable();
            $table->integer('categoryId')->unsigned();
            $table->integer('userId')->unsigned();
            $table->timestamps();
            

            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('categoryId')->references('id')->on('categories')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}
