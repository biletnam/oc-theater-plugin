<?php namespace Abnmt\Theater\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePressTable extends Migration
{

    public function up()
    {
        Schema::create('abnmt_theater_press', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->string('title');
            $table->string('slug')->index();
            $table->text('content')->nullable()->default(null);
            $table->string('source')->nullable()->default(null);
            $table->string('source_author')->nullable()->default(null);
            $table->date('source_date')->nullable()->default(null);
            $table->string('source_link')->nullable()->default(null);

            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('abnmt_theater_press');
    }

}