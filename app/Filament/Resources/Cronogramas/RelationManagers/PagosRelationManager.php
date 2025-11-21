<?php

namespace App\Filament\Resources\Cronogramas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use App\Enums\EstadoPago;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use App\Models\Pago;

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos'; // Asegúrate que en Modelo Cronograma exista "public function pagos()"

    protected static ?string $title = 'Pagos';

    protected static ?string $recordTitleAttribute = 'nro_cuota';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function  form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('nro_cuota')
                    ->label('Nro. Cuota')
                    ->numeric()
                    ->required()
                    ->columnSpan(1),

                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->placeholder('Generado automáticamente')
                    ->disabled() // Generalmente el código no se edita manual
                    ->dehydrated(false) // No se envía al guardar si está disabled
                    ->columnSpan(1),

                Forms\Components\TextInput::make('monto')
                    ->label('Monto (S/)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Select::make('estado')
                    ->label('Estado')
                    ->options(EstadoPago::class) // Carga las opciones del Enum automáticamente
                    ->required()
                    ->native(false)
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('fecha_vencimiento')
                    ->label('Fecha Vencimiento')
                    ->required()
                    ->native(false),

                Forms\Components\DatePicker::make('fecha_pago')
                    ->label('Fecha de Pago')
                    ->native(false),
                
                Forms\Components\TextInput::make('metodo_pago')
                    ->label('Método de Pago')
                    ->placeholder('Ej. Transferencia, Yape...'),

                Forms\Components\FileUpload::make('evidencia_path')
                    ->label('Comprobante')
                    ->directory('pagos_evidencias')
                    ->visibility('private')
                    ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nro_cuota')
                    ->label('#')
                    // ->sortable()
                    ->alignCenter()
                    ->width(40),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('PEN')
                    ->sortable()
                    ->weight('bold'),

                // ESTADO (Usando tu Enum)
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                // EVIDENCIA (Imagen pequeña, clic para ver grande)
                Tables\Columns\ImageColumn::make('evidencia_path')
                    ->label('Voucher')
                    ->visibility('private') // Ajusta si usas disco público
                    ->circular()
                    ->stacked(),

                // DETALLES DE PAGO
                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Pagado')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->searchable(),

                // DATOS DE LIQUIDACIÓN
                Tables\Columns\TextColumn::make('num_liquidacion')
                    ->label('Liquidación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Opcional: oculto por defecto para ahorrar espacio

                Tables\Columns\TextColumn::make('fecha_liquidacion')
                    ->label('F. Liq.')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Tables\Actions\CreateActio  n::make(),
            ])
            ->recordActions([
                // EditAction::make(),
                Action::make('subir_evidencia')
                    ->label('Subir Evidencia')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('success')
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo'=>'Efectivo',
                                'transferencia'=>'Transferencia',
                                'Yape/Plin'=>'Yape/Plin',
                            ])
                            ->label('Método de pago'),
                        FileUpload::make('evidencia_path')
                            ->label('Archivo de evidencia')
                            ->acceptedFileTypes(['applications/pdf', 'image/*'])
                            ->required()
                    ])
                    ->action(function(Pago $record, array $data):void{
                        $fechaActual = now();
                        $record->update([
                            'evidencia_path'=>$data['evidencia_path'],
                            'metodo_pago'=>$data['metodo_pago'],
                            'estado'=>EstadoPago::PAGADO,
                            'fecha_pago'=>$fechaActual,
                        ]);
                    }),
                // DeleteAction::make(),
            ]);

            
    }
}