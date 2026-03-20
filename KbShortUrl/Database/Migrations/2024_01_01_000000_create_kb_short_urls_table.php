<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKbShortUrlsTable extends Migration
{
    public function up()
    {
        Schema::create('kb_short_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_id')->index();
            $table->string('locale', 10)->default('');
            $table->unsignedInteger('short_number');
            $table->string('short_code', 50);
            $table->string('short_url', 500);
            $table->text('long_url');
            $table->timestamps();

            $table->unique(['article_id', 'locale']);
            $table->unique('short_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kb_short_urls');
    }
}
