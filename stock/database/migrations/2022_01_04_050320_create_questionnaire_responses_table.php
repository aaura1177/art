<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionnaireResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaire_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('questionnaire_id')->nullable();
            $table->string('question_no')->nullable();
            $table->string('most_likely')->nullable();
            $table->string('least_likely')->nullable();
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
        Schema::dropIfExists('questionnaire_responses');
    }
}
