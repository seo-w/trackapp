<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $name, array $data = []): void
    {
        View::render($name, $data);
    }
}
