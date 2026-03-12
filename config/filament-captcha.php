<?php

use Filament\Support\Enums\Size;

return [

    'width' => 180,
    'height' => 50,

    'length' => 5,

    // solo caracteres fáciles de distinguir
    'charset' => '1 23456789',

    'background_color' => [255, 255, 255],

    'refresh_button' => [
        'icon' => 'heroicon-o-arrow-path',
        'size' => Size::Medium,
    ],

];