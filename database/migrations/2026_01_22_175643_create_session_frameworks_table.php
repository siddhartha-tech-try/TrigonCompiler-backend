<?php

// database/migrations/xxxx_xx_xx_create_session_frameworks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('session_frameworks', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->string('language');
            $table->string('framework')->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('ide_sessions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_frameworks');
    }
};
