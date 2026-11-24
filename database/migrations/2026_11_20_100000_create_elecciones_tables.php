<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('admin')->after('email'); // admin | directivo
        });

        Schema::create('elecciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('anio');
            $table->string('estado')->default('borrador'); // borrador|abierta|cerrada|publicada
            $table->boolean('permite_voto_en_blanco')->default(true);
            $table->text('ips_permitidas')->nullable(); // una IP por línea; vacío = sin restricción
            $table->timestamp('abierta_en')->nullable();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamp('publicada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleccion_id')->constrained('elecciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('listas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleccion_id')->constrained('elecciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('nombre');
            $table->string('sigla')->nullable();
            $table->string('color', 9)->default('#2563eb');
            $table->string('logo')->nullable();
            $table->timestamps();

            $table->unique(['eleccion_id', 'numero']);
        });

        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_id')->constrained('listas')->cascadeOnDelete();
            $table->foreignId('cargo_id')->constrained('cargos')->cascadeOnDelete();
            $table->string('apellido');
            $table->string('nombre');
            $table->string('curso')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->unique(['lista_id', 'cargo_id']);
        });

        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('token', 64)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('votantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleccion_id')->constrained('elecciones')->cascadeOnDelete();
            $table->string('dni', 15);
            $table->string('apellido');
            $table->string('nombre');
            $table->string('curso')->nullable();
            $table->boolean('ya_voto')->default(false);
            $table->timestamp('voto_en')->nullable();
            $table->foreignId('puesto_id')->nullable()->constrained('puestos')->nullOnDelete();
            $table->timestamps();

            $table->unique(['eleccion_id', 'dni']);
        });

        // Urna: solo totales por lista. SIN votante, SIN timestamps => el voto es secreto.
        Schema::create('conteo_votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleccion_id')->constrained('elecciones')->cascadeOnDelete();
            $table->foreignId('lista_id')->nullable()->constrained('listas')->cascadeOnDelete(); // null = en blanco
            $table->unsignedInteger('cantidad')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteo_votos');
        Schema::dropIfExists('votantes');
        Schema::dropIfExists('puestos');
        Schema::dropIfExists('candidatos');
        Schema::dropIfExists('listas');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('elecciones');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('rol'));
    }
};
