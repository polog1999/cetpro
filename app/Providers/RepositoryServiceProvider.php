<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para registrar los bindings de Repositorios.
 * 
 * Este provider implementa el principio de Dependency Inversion (DIP),
 * permitiendo que las capas superiores dependan de abstracciones (interfaces)
 * en lugar de implementaciones concretas.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registrar los bindings de repositorios en el contenedor.
     *
     * @return void
     */
    public function register(): void
    {
        // ============================================
        // Módulo Autenticación
        // ============================================
        
        $this->app->bind(
            \App\Repositories\PermisoRepositoryInterface::class,
            \App\Repositories\Eloquent\PermisoRepository::class
        );

        $this->app->bind(
            \App\Repositories\RoleRepositoryInterface::class,
            \App\Repositories\Eloquent\RoleRepository::class
        );

        $this->app->bind(
            \App\Repositories\UsuarioRepositoryInterface::class,
            \App\Repositories\Eloquent\UsuarioRepository::class
        );

        // ============================================
        // Módulo RRHH
        // ============================================
        
        $this->app->bind(
            \App\Repositories\DocenteRepositoryInterface::class,
            \App\Repositories\Eloquent\DocenteRepository::class
        );

        $this->app->bind(
            \App\Repositories\EmpleadoRepositoryInterface::class,
            \App\Repositories\Eloquent\EmpleadoRepository::class
        );

        // ============================================
        // Módulo Estudiantes
        // ============================================
        
        $this->app->bind(
            \App\Repositories\EstudianteRepositoryInterface::class,
            \App\Repositories\Eloquent\EstudianteRepository::class
        );

        $this->app->bind(
            \App\Repositories\ApoderadoRepositoryInterface::class,
            \App\Repositories\Eloquent\ApoderadoRepository::class
        );

        // ============================================
        // Módulo Académico
        // ============================================
        
        $this->app->bind(
            \App\Repositories\EspecialidadRepositoryInterface::class,
            \App\Repositories\Eloquent\EspecialidadRepository::class
        );

        $this->app->bind(
            \App\Repositories\ProgramaRepositoryInterface::class,
            \App\Repositories\Eloquent\ProgramaRepository::class
        );

        $this->app->bind(
            \App\Repositories\CursoRepositoryInterface::class,
            \App\Repositories\Eloquent\CursoRepository::class
        );

        $this->app->bind(
            \App\Repositories\HorarioRepositoryInterface::class,
            \App\Repositories\Eloquent\HorarioRepository::class
        );

        // ============================================
        // Módulo Matrículas
        // ============================================
        
        $this->app->bind(
            \App\Repositories\MatriculaRepositoryInterface::class,
            \App\Repositories\Eloquent\MatriculaRepository::class
        );

        $this->app->bind(
            \App\Repositories\CronogramaRepositoryInterface::class,
            \App\Repositories\Eloquent\CronogramaRepository::class
        );

        $this->app->bind(
            \App\Repositories\PagoRepositoryInterface::class,
            \App\Repositories\Eloquent\PagoRepository::class
        );
    }

    /**
     * Bootstrap de servicios.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
