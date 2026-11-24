<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestos', function (Blueprint $table) {
            // MAC de las placas de red de la PC vinculada, separadas por coma. null = sin vincular.
            $table->string('mac')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('puestos', function (Blueprint $table) {
            $table->dropColumn('mac');
        });
    }
};
