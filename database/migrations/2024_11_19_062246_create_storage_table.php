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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key to users table
            $table->foreignId('web_id')->constrained('web')->onDelete('cascade'); // Foreign key to web table
            $table->foreignId('role_id')->default(1)->constrained('roles')->onDelete('cascade'); // Foreign key to roles table, default 1 (admin)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage');
    }
};
?>
