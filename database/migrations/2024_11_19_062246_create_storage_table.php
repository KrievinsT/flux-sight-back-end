<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->string('url');
            $table->string('seo');
            $table->float('page_speed');
            $table->boolean('is_active')->default(true);
            $table->foreignId('role_id')->default(1)->constrained()->onDelete('cascade'); // Foreign key to roles table, default 1 (admin)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage');
    }
};
