<?php

use App\Models\Ferry;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ferries = Ferry::all();
$output = '';
foreach ($ferries as $ferry) {
    $output .= "ID: {$ferry->id}, Name: {$ferry->name}, Operator: {$ferry->operator}\n";
}

file_put_contents('ferries_list.txt', $output);
