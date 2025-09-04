<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // Primary key UUID
            $table->uuid('id')->primary();

            $table->string('name'); // nama kategori (wajib)
            $table->text('description')->nullable(); // deskripsi (opsional)
            $table->string('image_url')->nullable(); // URL gambar kategori
            $table->timestamps(); // created_at & updated_at
        });

        // Kalau pakai PostgreSQL, bisa bikin default UUID generator
        DB::statement('ALTER TABLE categories ALTER COLUMN id SET DEFAULT gen_random_uuid();');
        // Pastikan extension uuid-ossp atau pgcrypto aktif
        // run dulu: CREATE EXTENSION IF NOT EXISTS "pgcrypto";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
