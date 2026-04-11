<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AdminTokenService;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();

        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        $users = $repo->all();

        $this->view('usuarios/index', [
            'title' => 'Gestión de Usuarios',
            'users' => $users,
            'flashSuccess' => flash_take('user_success'),
            'flashError' => flash_take('user_error'),
        ]);
    }

    public function approve(): void
    {
        $this->requireAdmin();
        if (! csrf_validate()) {
            flash('user_error', 'Token inválido.');
            redirect('/usuarios');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        
        $repo->update($id, ['is_approved' => 1]);
        
        flash('user_success', 'Usuario aprobado correctamente.');
        redirect('/usuarios');
    }

    public function unapprove(): void
    {
        $this->requireAdmin();
        if (! csrf_validate()) {
            flash('user_error', 'Token inválido.');
            redirect('/usuarios');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        
        $repo->update($id, ['is_approved' => 0]);
        
        // Si el usuario se despoja a sí mismo de aprobación (poco probable p. ej. error),
        // pero por si acaso, no forzamos logout aquí ya que el requireAuth lo manejará
        
        flash('user_success', 'Aprobación de usuario revocada.');
        redirect('/usuarios');
    }

    public function promote(): void
    {
        $this->requireAdmin();
        if (! csrf_validate()) {
            flash('user_error', 'Token inválido.');
            redirect('/usuarios');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        
        $repo->update($id, ['role' => 'admin', 'is_approved' => 1]);
        
        flash('user_success', 'Usuario promovido a Administrador.');
        redirect('/usuarios');
    }

    public function demote(): void
    {
        $this->requireAdmin();
        if (! csrf_validate()) {
            flash('user_error', 'Token inválido.');
            redirect('/usuarios');
        }

        $id = (int) ($_POST['id'] ?? 0);
        
        // No permitir quitarse el admin a sí mismo si es el único
        if ($id === session()->get('local_user_id')) {
            flash('user_error', 'No puedes quitarte el rol de administrador a ti mismo desde aquí.');
            redirect('/usuarios');
        }

        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        $repo->update($id, ['role' => 'user']);
        
        flash('user_success', 'Usuario degradado a rol de consultor.');
        redirect('/usuarios');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        if (! csrf_validate()) {
            flash('user_error', 'Token inválido.');
            redirect('/usuarios');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === session()->get('local_user_id')) {
            flash('user_error', 'No puedes eliminarte a ti mismo de la base de datos.');
            redirect('/usuarios');
        }

        $pdo = db()->pdo();
        $repo = new UserRepository($pdo);
        $repo->delete($id);
        
        flash('user_success', 'Usuario eliminado permanentemente.');
        redirect('/usuarios');
    }
}
