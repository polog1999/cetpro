<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Usuario;
use App\Models\Role;
use App\Models\Docente;
use App\Models\Programa;
use App\Enums\Turno;
use App\Enums\Modalidad;
use App\Enums\TipoPrograma;
use App\Enums\Tip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\Horarios\Pages\CreateHorario;

class HorariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Admin User
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $this->admin = Usuario::factory()->create(['role_id' => $rolAdmin->id]);
    }

    public function test_no_permite_crear_seccion_con_choque_de_horario_para_mismo_docente()
    {
        $docente = Docente::factory()->create();
        $programa = Programa::factory()->create();

        // Crear horario existente: Lunes 08:00 - 10:00
        Horario::create([
            'id_programa' => $programa->id_programa,
            'id_docente' => $docente->id,
            'dias' => ['LUN'],
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'turno' => Turno::MAÑANA,
            'modalidad' => Modalidad::PRESENCIAL,
            'aula' => '101'
        ]);

        // Intentar crear otro horario solapado: Lunes 09:00 - 11:00 (Mismo docente)
        Livewire::actingAs($this->admin)
            ->test(CreateHorario::class)
            ->set('data.tipo_programa', Tip::PROGRAMA->value)
            ->set('data.id_programa', $programa->id_programa)
            ->set('data.id_docente', $docente->id)
            ->set('data.dias', ['LUN'])
            ->set('data.hora_inicio', '09:00')
            ->set('data.hora_fin', '11:00')
            ->set('data.turno', Turno::MAÑANA->value)
            ->set('data.modalidad', Modalidad::PRESENCIAL->value)
            ->set('data.aula', '102') // Diferente aula, pero mismo docente
            ->call('create')
            ->assertHasErrors();
    }

    public function test_no_permite_crear_seccion_con_choque_de_horario_para_misma_aula()
    {
        $docente1 = Docente::factory()->create();
        $docente2 = Docente::factory()->create();
        $programa = Programa::factory()->create();

        // Crear horario existente: Martes 14:00 - 16:00 en Aula 202
        Horario::create([
            'id_programa' => $programa->id_programa,
            'id_docente' => $docente1->id,
            'dias' => ['MAR'],
            'hora_inicio' => '14:00',
            'hora_fin' => '16:00',
            'turno' => Turno::TARDE,
            'modalidad' => Modalidad::PRESENCIAL,
            'aula' => '202'
        ]);

        // Intentar crear otro horario solapado: Martes 15:00 - 17:00 (Misma aula 202)
        Livewire::actingAs($this->admin)
            ->test(CreateHorario::class)
            ->set('data.tipo_programa', Tip::PROGRAMA->value)
            ->set('data.id_programa', $programa->id_programa)
            ->set('data.id_docente', $docente2->id) // Diferente docente
            ->set('data.dias', ['MAR'])
            ->set('data.hora_inicio', '15:00')
            ->set('data.hora_fin', '17:00')
            ->set('data.turno', Turno::TARDE->value)
            ->set('data.modalidad', Modalidad::PRESENCIAL->value)
            ->set('data.aula', '202') // Misma aula
            ->call('create')
            ->assertHasErrors();
    }
}
