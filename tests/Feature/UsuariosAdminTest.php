<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('el admin ve la gestión de usuarios y el directivo no', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
    $this->actingAs(User::factory()->directivo()->create())->get('/admin/users')->assertForbidden();
});

it('crea un usuario como directivo por defecto y guarda la clave hasheada', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(CreateUser::class)
        ->assertFormSet(['rol' => User::ROL_DIRECTIVO, 'activo' => true])
        ->fillForm([
            'name' => 'Ana Directiva',
            'email' => 'ana@ejemplo.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ana = User::where('email', 'ana@ejemplo.test')->firstOrFail();
    expect($ana->rol)->toBe(User::ROL_DIRECTIVO)
        ->and($ana->activo)->toBeTrue()
        ->and(Hash::check('clave-segura-1', $ana->password))->toBeTrue();
});

it('rechaza claves cortas, que no coinciden o correos repetidos', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(CreateUser::class)
        ->fillForm([
            'name' => 'Ana',
            'email' => $admin->email,
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ])
        ->call('create')
        ->assertHasFormErrors(['email', 'password']);
});

it('cambia la contraseña desde la acción de la tabla', function () {
    $admin = User::factory()->admin()->create();
    $directivo = User::factory()->directivo()->create();

    Livewire::actingAs($admin)->test(ListUsers::class)
        ->callAction(TestAction::make('cambiarPassword')->table($directivo), [
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])
        ->assertHasNoFormErrors();

    expect(Hash::check('nueva-clave-123', $directivo->fresh()->password))->toBeTrue();
});

it('un directivo desactivado no entra al panel ni al acta', function () {
    $inactivo = User::factory()->directivo()->inactivo()->create();

    expect($inactivo->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    $this->actingAs($inactivo)->get('/acta')->assertForbidden();
});

it('no permite borrarse a sí mismo ni al último admin activo', function () {
    $admin = User::factory()->admin()->create();
    $otroAdmin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(ListUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($admin))
        ->assertActionVisible(TestAction::make('delete')->table($otroAdmin));

    $otroAdmin->update(['activo' => false]);

    expect($admin->fresh()->esUltimoAdminActivo())->toBeTrue();
    Livewire::actingAs($admin)->test(ListUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($admin));
});

it('no permite cambiar el propio rol ni el estado', function () {
    $admin = User::factory()->admin()->create();
    $directivo = User::factory()->directivo()->create();

    Livewire::actingAs($admin)->test(EditUser::class, ['record' => $admin->getKey()])
        ->assertFormFieldIsDisabled('rol')
        ->assertFormFieldIsDisabled('activo');

    Livewire::actingAs($admin)->test(EditUser::class, ['record' => $directivo->getKey()])
        ->assertFormFieldIsEnabled('rol')
        ->assertFormFieldIsEnabled('activo');
});

it('registra el último acceso al iniciar sesión', function () {
    $directivo = User::factory()->directivo()->create();
    expect($directivo->ultimo_acceso_at)->toBeNull();

    event(new Login('web', $directivo, false));

    expect($directivo->fresh()->ultimo_acceso_at)->not->toBeNull();
});
