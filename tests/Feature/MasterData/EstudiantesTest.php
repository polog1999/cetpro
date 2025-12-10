<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Role;
use App\Enums\TipoDocumento;
use App\Enums\TipoGenero;
use App\Enums\EstadoCivil;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\Estudiantes\Pages\CreateEstudiante;

class EstudiantesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $this->admin = Usuario::factory()->create(['role_id' => $rolAdmin->id]);
    }

    public function test_dni_de_estudiante_debe_ser_unico()
    {
        $estudiante = Estudiante::factory()->create(['nro_documento' => '12345678']);

        Livewire::actingAs($this->admin)
            ->test(CreateEstudiante::class)
            ->set('data.nro_documento', '12345678')
            ->set('data.nombres', 'Otro')
            ->set('data.apellido_paterno', 'Estudiante')
            ->set('data.apellido_materno', 'Test')
            ->set('data.tipo_documento', TipoDocumento::DNI->value)
            ->set('data.genero', TipoGenero::MASCULINO->value)
            ->set('data.estado_civil', EstadoCivil::SOLTERO->value)
            ->set('data.grado_instruccion', GradoInstruccion::SECUNDARIA_COMPLETA->value)
            ->set('data.provincia', Provincia::LIMA->value)
            ->set('data.distrito', DistritoLima::LIMA->value)
            ->set('data.email', 'otro@test.com')
            ->set('data.telefono', '987654321')
            ->call('create')
            ->assertHasErrors(['data.nro_documento']);
    }

    public function test_se_puede_crear_estudiante_con_datos_minimos()
    {
        Livewire::actingAs($this->admin)
            ->test(CreateEstudiante::class)
            ->set('data.tipo_documento', TipoDocumento::DNI->value)
            ->set('data.nro_documento', '99999999')
            ->set('data.nombres', 'Nuevo')
            ->set('data.apellido_paterno', 'Estudiante')
            ->set('data.apellido_materno', 'Minimo')
            ->set('data.genero', TipoGenero::FEMENINO->value)
            ->set('data.estado_civil', EstadoCivil::SOLTERO->value)
            ->set('data.grado_instruccion', GradoInstruccion::SECUNDARIA_COMPLETA->value)
            ->set('data.provincia', Provincia::LIMA->value)
            ->set('data.distrito', DistritoLima::LIMA->value)
            ->set('data.email', 'nuevo@test.com')
            ->set('data.telefono', '912345678')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estudiantes', ['nro_documento' => '99999999']);
    }
}
