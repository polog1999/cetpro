<?php

namespace Tests\Feature;

use App\Models\Programa;
use App\Models\Usuario;
use App\Models\Role;
use App\Enums\TipoPrograma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Filament\Resources\Programas\Pages\EditPrograma;

class ProgramasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $this->admin = Usuario::factory()->create(['role_id' => $rolAdmin->id]);
    }

    public function test_no_se_pueden_crear_programas_con_nombre_duplicado()
    {
        $programa = Programa::factory()->create(['nombre_programa' => 'Desarrollo Web']);

        Livewire::actingAs($this->admin)
            ->test(CreatePrograma::class)
            ->set('data.nombre_programa', 'Desarrollo Web')
            ->set('data.tipo_programa', TipoPrograma::PROGRAMA_ESTUDIO->value)
            ->set('data.id_especialidad', $programa->id_especialidad)
            ->call('create')
            ->assertHasErrors(['data.nombre_programa']);
    }

    public function test_se_pueden_crear_y_editar_programas_correctamente()
    {
        $especialidad = \App\Models\Especialidad::factory()->create();

        // Crear
        Livewire::actingAs($this->admin)
            ->test(CreatePrograma::class)
            ->set('data.nombre_programa', 'Nuevo Programa')
            ->set('data.tipo_programa', TipoPrograma::PROGRAMA_ESTUDIO->value)
            ->set('data.id_especialidad', $especialidad->id_especialidad)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programas', ['nombre_programa' => 'Nuevo Programa']);

        $programa = Programa::first();

        // Editar
        Livewire::actingAs($this->admin)
            ->test(EditPrograma::class, ['record' => $programa->getKey()])
            ->set('data.nombre_programa', 'Programa Editado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programas', ['nombre_programa' => 'Programa Editado']);
    }
}
