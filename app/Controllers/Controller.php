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

    protected function requireAuth(): void
    {
        if (! session()->has('user_id')) {
            redirect('/login');
        }

        // Si el usuario está autenticado pero NO está aprobado por el administrador
        if (! session()->get('user_approved', false)) {
            // Permitir solo la página de espera de aprobación o logout
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (! str_contains($uri, '/waiting-approval') && ! str_contains($uri, '/logout')) {
                redirect('/waiting-approval');
            }
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (session()->get('user_role') !== 'admin' && session()->get('user_role') !== 'superadmin') {
             flash('auth_error', 'Acceso restringido: Solo administradores.');
             redirect('/');
        }
    }
}
