<?php
declare(strict_types=1);

// Wywolywane co minute przez kontener scheduler.
// Etap 2: rozliczanie zakonczonych szkolen i prac, powiadomienia, e-maile.
require __DIR__ . '/../vendor/autoload.php';

echo gmdate('c'), " scheduler tick\n";
