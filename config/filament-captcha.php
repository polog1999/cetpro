<?php

use Filament\Support\Enums\Size;

return [

    // optional, default is 5
    // 'length' => 4,

    // optional, default is 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    // 'charset' => '123456789',

    // Tamaño de la imagen CAPTCHA
    'width' => 380,    // ancho igual que los inputs
    'height' => 80,   // alto suficiente para que no se corte el texto

    // Opciones adicionales
    'length' => 5,
    'charset' => 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',

    'background_color' => [255, 255, 255],

    'refresh_button' => [
        'icon' => 'heroicon-o-arrow-path',
        'size' => Size::Medium,
    ],

];
