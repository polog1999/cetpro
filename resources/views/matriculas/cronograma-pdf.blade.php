<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cronograma de Pagos</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { width: 100%; border-bottom: 2px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .subtitle { font-size: 14px; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .label { font-weight: bold; color: #555; }
        
        .pagos-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .pagos-table th, .pagos-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .pagos-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .pagos-table tr:nth-child(even) { background-color: #fafafa; }
        
        .status-badge { padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-pendiente { background-color: #fef3c7; color: #d97706; }
        .status-pagado { background-color: #d1fae5; color: #059669; }
        .status-vencido { background-color: #fee2e2; color: #dc2626; }
        .status-anulado { background-color: #f3f4f6; color: #4b5563; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 10px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .totals { margin-top: 20px; text-align: right; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Cronograma de Pagos</div>
        <div class="subtitle">Matrícula N° {{ $matricula->codigo_inscripcion }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Estudiante:</td>
            <td>{{ $matricula->estudiante->nombre_completo }}</td>
            <td class="label">Fecha Inscripción:</td>
            <td>{{ $matricula->created_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Programa / Curso:</td>
            <td>
                @php
                    $programa = match($matricula->tipo_matricula) {
                        \App\Enums\TipoMatricula::PROGRAMA => $matricula->horario?->programa?->nombre_programa,
                        \App\Enums\TipoMatricula::FORMACION_CONTINUA => $matricula->horario?->programa?->nombre_programa,
                        \App\Enums\TipoMatricula::CURSO => $matricula->curso?->nombre_curso,
                        \App\Enums\TipoMatricula::MODULO => $matricula->curso?->nombre_curso,
                        default => 'N/A'
                    };
                @endphp
                {{ $programa }}
            </td>
            <td class="label">Tipo Matrícula:</td>
            <td>{{ $matricula->tipo_matricula->getLabel() ?? ucfirst($matricula->tipo_matricula->value) }}</td>
        </tr>
    </table>

    <table class="pagos-table">
        <thead>
            <tr>
                <th>Nro Recibo</th>
                <th>Nro Liquidación</th>
                <th>Vencimiento</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Fecha Pago</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matricula->cronograma->pagos as $pago)
                <tr>
                    <td>{{ $pago->nro_cuota }}</td>
                    <td>{{ $pago->num_liquidacion ?? '-' }}</td>
                    <td>{{ $pago->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td>S/. {{ number_format($pago->monto, 2) }}</td>
                    <td>
                        @php
                            $estado = strtolower($pago->estado ?? '');
                            $class = match(true) {
                                str_contains($estado, 'pagado') || str_contains($estado, 'cancelado') => 'status-pagado',
                                str_contains($estado, 'pendiente') => 'status-pendiente',
                                str_contains($estado, 'vencido') => 'status-vencido',
                                str_contains($estado, 'anulado') => 'status-anulado',
                                default => 'status-anulado'
                            };
                        @endphp
                        <span class="status-badge {{ $class }}">{{ $pago->estado }}</span>
                    </td>
                    <td>{{ $pago->fecha_pago?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <strong>Monto Total:</strong> S/. {{ number_format($matricula->cronograma->monto_total, 2) }} <br>
        <strong>Saldo Pendiente:</strong> 
        <span style="color: #dc2626;">S/. {{ number_format($matricula->cronograma->pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'pendiente'))->sum('monto'), 2) }}</span>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
