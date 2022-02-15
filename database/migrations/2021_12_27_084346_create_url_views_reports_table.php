<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUrlViewsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('url_views_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('url_id');
            $table->foreign('url_id')->references('id')->on('observable_urls');

            $table->date('day')->index();

            $table->integer('views');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('url_views_reports');
    }
}
