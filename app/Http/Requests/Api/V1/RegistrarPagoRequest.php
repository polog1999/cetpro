<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metodo_pago' => 'required|string|in:Efectivo,Transferencia,Tarjeta,Yape,Plin',
            'evidencia_path' => 'nullable|string|max:255',
            'usuario_id' => 'nullable|exists:usuarios,id',
        ];
    }
}
