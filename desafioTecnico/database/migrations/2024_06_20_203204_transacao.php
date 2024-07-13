<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transacao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conta_numeroDaConta')->unsigned();
            $table->string('tipo', 45);
            $table->double('valor')->nullable();
            $table->string('moeda', 45);
            $table->timestamps();
            $table->foreign('conta_numeroDaConta')->references('numeroDaConta')->on('conta')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacao');
    }
};
