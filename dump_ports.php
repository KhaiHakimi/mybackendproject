<?php

use App\Models\Port;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ports = Port::all();
$output = '';
foreach ($ports as $port) {
    $output .= "ID: {$port->id}, Name: {$port->name}, Location: {$port->location}\n";
}

file_put_contents('ports_list.txt', $output);
