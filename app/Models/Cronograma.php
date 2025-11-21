<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cronograma extends Model
{
    use HasFactory;

    // Si la tabla se llama "cronogramas" no hace falta $table
    // protected $table = 'cronogramas';

    protected $fillable = [
        'matricula_id',
        'num_cuotas',
        'monto_total',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
    ];

    /**
     * Cronograma pertenece a una matrícula.
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * Cronograma tiene muchos pagos (cuotas).
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}

