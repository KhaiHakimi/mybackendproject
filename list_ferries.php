<?php

use App\Models\Ferry;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ferries = Ferry::all()->map(function ($f) {
    return [
        'id' => $f->id,
        'name' => $f->name,
        'operator' => $f->operator,
    ];
});

echo json_encode($ferries, JSON_PRETTY_PRINT);
