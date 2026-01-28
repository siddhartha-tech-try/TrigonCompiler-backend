<?php

// database/migrations/xxxx_xx_xx_create_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ide_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->string('status')->default('active'); // active | expired | promoted

            $table->string('workspace_path');

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ide_sessions');
    }
};

