<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('rol')->default('admin_empresa');
            $table->timestamps();

            $table->unique(['empresa_id', 'user_id']);
        });

        // Migrar relaciones existentes: cada usuario con empresa_id → entra al pivot
        $users = DB::table('users')->whereNotNull('empresa_id')->get();

        foreach ($users as $user) {
            $rol = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->whereNotIn('roles.name', ['super_admin'])
                ->value('roles.name') ?? 'admin_empresa';

            DB::table('empresa_user_access')->insertOrIgnore([
                'empresa_id' => $user->empresa_id,
                'user_id'    => $user->id,
                'rol'        => $rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_user_access');
    }
};
