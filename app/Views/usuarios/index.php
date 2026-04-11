<?php

declare(strict_types=1);

/** @var string $title */
/** @var list<array<string, mixed>> $users */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */

?>
<div class="container py-5" x-data="{ 
    search: '',
    filterRole: 'all',
    filterStatus: 'all',
    users: <?= htmlspecialchars(json_encode(array_map(function($u) {
        return [
            'id' => $u['id'],
            'email' => strtolower((string)$u['email']),
            'tienda' => strtolower((string)($u['tienda_name'] ?? '')),
            'role' => (string)$u['role'],
            'approved' => (bool)$u['is_approved']
        ];
    }, $users))) ?>,
    isVisible(u) {
        if (!u) return true;
        const s = this.search.toLowerCase();
        const matchesSearch = !this.search || u.email.includes(s) || u.tienda.includes(s);
        const matchesRole = this.filterRole === 'all' || u.role === this.filterRole;
        const matchesStatus = this.filterStatus === 'all' || 
                             (this.filterStatus === 'approved' && u.approved) || 
                             (this.filterStatus === 'pending' && !u.approved);
        return matchesSearch && matchesRole && matchesStatus;
    },
    get visibleCount() {
        return this.users.filter(u => this.isVisible(u)).length;
    }
}">
    <!-- Header Hero Section -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-7">
            <h1 class="h2 fw-bold track-page-title mb-1">
                <i class="bi bi-shield-check-fill me-2 text-primary opacity-75"></i><?= htmlspecialchars($title) ?>
            </h1>
            <p class="track-page-lead mb-0 opacity-75">Control centralizado de identidades y permisos.</p>
        </div>
        <div class="col-lg-5 text-lg-end mt-4 mt-lg-0">
            <div class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25 px-4 py-2 rounded-pill">
                <span class="small fw-bold"><span x-text="visibleCount"></span> de <?= count($users) ?> Usuarios</span>
            </div>
        </div>
    </div>

    <!-- Smart Filter Bar -->
    <div class="card border-0 glass-depth rounded-4 mb-4 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-3">
                <!-- Search Identity -->
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm bg-surface-nav rounded-pill px-3">
                        <span class="input-group-text bg-transparent border-0 opacity-50 px-0"><i class="bi bi-search"></i></span>
                        <input type="text" x-model="search" class="form-control bg-transparent border-0 shadow-none py-2 text-color-main" placeholder="Buscar por email o tienda...">
                    </div>
                </div>
                
                <!-- Role Filter -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm bg-surface-nav rounded-pill px-3">
                        <span class="input-group-text bg-transparent border-0 opacity-50 px-0"><i class="bi bi-filter"></i></span>
                        <select x-model="filterRole" class="form-select bg-transparent border-0 shadow-none py-2 cursor-pointer small text-color-main">
                            <option value="all" class="bg-dark text-white">Todos los Roles</option>
                            <option value="admin" class="bg-dark text-white">Administradores</option>
                            <option value="user" class="bg-dark text-white">Consultores</option>
                        </select>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm bg-surface-nav rounded-pill px-3">
                        <span class="input-group-text bg-transparent border-0 opacity-50 px-0"><i class="bi bi-toggle-on"></i></span>
                        <select x-model="filterStatus" class="form-select bg-transparent border-0 shadow-none py-2 cursor-pointer small text-color-main">
                            <option value="all" class="bg-dark text-white">Todos los Estados</option>
                            <option value="approved" class="bg-dark text-white">Acceso Activo</option>
                            <option value="pending" class="bg-dark text-white">Acceso Bloqueado</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters (Optional) -->
                <div class="col-12 col-lg-2 text-center text-lg-end" x-show="search || filterRole !== 'all' || filterStatus !== 'all'">
                    <button @click="search=''; filterRole='all'; filterStatus='all'" class="btn btn-link btn-sm text-muted text-decoration-none opacity-75 hover-opacity-100">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages (Flash) -->
    <div class="mb-4">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-glass-success border-0 rounded-4 p-3 d-flex align-items-center gap-3 animate-fade-in shadow-sm">
                <div class="icon-circle-sm bg-success text-white"><i class="bi bi-check-lg"></i></div>
                <div class="fw-semibold small"><?= htmlspecialchars($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-glass-danger border-0 rounded-4 p-3 d-flex align-items-center gap-3 animate-fade-in shadow-sm">
                <div class="icon-circle-sm bg-danger text-white"><i class="bi bi-x-lg"></i></div>
                <div class="fw-semibold small"><?= htmlspecialchars($flashError) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Users Table Card -->
    <div class="card track-card border-0 shadow-2xl overflow-hidden glass-depth">
        <div class="table-responsive">
            <table class="table table-premium table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">
                            <span class="small text-uppercase opacity-50 tracking-widest">Identidad</span>
                        </th>
                        <th>
                            <span class="small text-uppercase opacity-50 tracking-widest">Nivel / Rol</span>
                        </th>
                        <th>
                            <span class="small text-uppercase opacity-50 tracking-widest">Estado</span>
                        </th>
                        <th class="text-center pe-4">
                            <span class="small text-uppercase opacity-50 tracking-widest">Controles</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="table-striped-soft border-top border-white border-opacity-10">
                    <?php foreach ($users as $user): 
                        $userData = json_encode([
                            'email' => strtolower((string)$user['email']),
                            'tienda' => strtolower((string)($user['tienda_name'] ?? '')),
                            'role' => (string)$user['role'],
                            'approved' => (bool)$user['is_approved']
                        ]);
                    ?>
                        <tr x-show="isVisible(<?= htmlspecialchars($userData, ENT_QUOTES, 'UTF-8') ?>)" x-transition:enter="fade-in">
                            <td class="ps-4 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar shadow-lg rounded-circle me-3">
                                        <div class="avatar-content bg-gradient-brand">
                                            <?= strtoupper(substr($user['email'], 0, 1)) ?>
                                        </div>
                                        <div class="status-indicator <?= $user['is_approved'] ? 'active' : 'pending' ?>"></div>
                                    </div>
                                    <div class="line-height-tight">
                                        <div class="fw-bold fs-6 mb-1 text-color-main"><?= htmlspecialchars($user['email']) ?></div>
                                        <?php if (!empty($user['tienda_name'])): ?>
                                            <div class="shop-badge d-inline-flex align-items-center">
                                                <i class="bi bi-shop me-1"></i> <?= htmlspecialchars($user['tienda_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-muted-compact mt-1">
                                            <i class="bi bi-clock me-1"></i> <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <div class="role-badge admin">
                                        <i class="bi bi-shield-fill-check me-2"></i><span>FULL ACCESS</span>
                                    </div>
                                <?php else: ?>
                                    <div class="role-badge user">
                                        <i class="bi bi-person-fill me-2"></i><span>CONSULTOR</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_approved']): ?>
                                    <div class="status-pill status-approved">
                                        <span class="dot pulse"></span>
                                        <span class="text">ACTIVO</span>
                                    </div>
                                <?php else: ?>
                                    <div class="status-pill status-pending">
                                        <span class="dot"></span>
                                        <span class="text">BLOQUEADO</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <div class="action-container d-inline-flex align-items-center p-1 rounded-pill bg-action-panel">
                                    <?php if (! $user['is_approved']): ?>
                                        <form action="/usuarios/approve" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-action-main approve" title="Otorgar Acceso">
                                                <i class="bi bi-person-check-fill"></i>
                                                <span class="ms-1 d-none d-xl-inline">Activar</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/usuarios/unapprove" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-icon-only text-warning" title="Suspender Acceso">
                                                <i class="bi bi-slash-circle-fill"></i>
                                            </button>
                                        </form>
                                        
                                        <div class="divider-vertical ms-2 me-1"></div>

                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <form action="/usuarios/promote" method="POST" class="m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-icon-only text-info" title="Promover a Admin">
                                                    <i class="bi bi-arrow-up-circle-fill"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="/usuarios/demote" method="POST" class="m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-icon-only text-secondary" title="Degradar a Consultor">
                                                    <i class="bi bi-arrow-down-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <div class="divider-vertical ms-1 me-2"></div>

                                        <form action="/usuarios/delete" method="POST" class="m-0" onsubmit="return confirm('¿Eiminar permanentemente?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-icon-only text-danger delete" title="Eliminar Definitivamente">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div x-show="visibleCount === 0" class="text-center py-5 opacity-50">
            <i class="bi bi-search display-4 mb-3 d-block"></i>
            <h5 class="fw-bold">No hay resultados para estos filtros</h5>
            <button @click="search=''; filterRole='all'; filterStatus='all'" class="btn btn-outline-primary btn-sm rounded-pill mt-2 px-4">Ver todos</button>
        </div>
    </div>
</div>

<style>
    :root {
        --track-brand-gradient: linear-gradient(135deg, #00ffff 0%, #00d2ff 100%);
        --track-role-admin: #00ffff;
        --track-role-user: #94a3b8;
        --track-status-ok: #20c997;
        --track-status-wait: #f59e0b;
    }

    /* Page Elements */
    .bg-surface-nav { background: rgba(255,255,255,0.05); }
    .tracking-widest { letter-spacing: 0.15em; font-weight: 800; font-size: 0.65rem !important; }
    .text-color-main { color: var(--track-text); }
    
    /* Avatar System */
    .user-avatar { width: 44px; height: 44px; position: relative; }
    .avatar-content { 
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; 
        border-radius: 50%; font-weight: 800; color: #000; font-size: 1.2rem;
    }
    .bg-gradient-brand { background: var(--track-brand-gradient); }
    .status-indicator { 
        position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; 
        border-radius: 50%; border: 2px solid var(--track-card-bg);
    }
    .status-indicator.active { background: var(--track-status-ok); }
    .status-indicator.pending { background: var(--track-status-wait); }

    /* Badges & Status */
    .shop-badge { 
        font-size: 0.75rem; font-weight: 700; color: var(--track-primary); 
        background: rgba(0, 255, 255, 0.08); padding: 0.1rem 0.6rem; border-radius: 4px;
    }
    .text-muted-compact { font-size: 0.7rem; color: var(--track-muted); opacity: 0.6; }
    
    .role-badge { display: inline-flex; align-items: center; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; }
    .role-badge.admin { color: var(--track-role-admin); }
    .role-badge.user { color: var(--track-role-user); opacity: 0.8; }
    
    .status-pill { display: inline-flex; align-items: center; gap: 8px; font-size: 0.7rem; font-weight: 800; }
    .status-pill .dot { width: 6px; height: 6px; border-radius: 50%; }
    .status-approved { color: var(--track-status-ok); }
    .status-approved .dot { background: var(--track-status-ok); box-shadow: 0 0 10px var(--track-status-ok); }
    .status-pending { color: var(--track-status-wait); }
    .status-pending .dot { background: var(--track-status-wait); }
    
    .pulse { animation: status-pulse 2s infinite; }
    @keyframes status-pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

    /* Action Panel */
    .bg-action-panel { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); }
    .btn-icon-only { 
        width: 32px; height: 32px; border: none; background: transparent; 
        display: flex; align-items: center; justify-content: center; 
        transition: all 0.25s ease; font-size: 1.1rem;
    }
    .btn-icon-only:hover { transform: scale(1.2); opacity: 1 !important; }
    .btn-icon-only.delete:hover { color: #ff24e4 !important; }
    
    .btn-action-main { 
        background: var(--track-primary); color: #000; border: none; font-weight: 800;
        font-size: 0.75rem; padding: 0.35rem 1rem; border-radius: 50px; text-transform: uppercase;
        box-shadow: 0 0 15px var(--track-accent-glow); transition: all 0.25s ease;
    }
    .btn-action-main:hover { transform: translateY(-2px); box-shadow: 0 5px 20px var(--track-accent-glow); filter: brightness(1.1); }
    
    .divider-vertical { width: 1px; height: 20px; background: rgba(255,255,255,0.1); }

    /* Alerts */
    .icon-circle-sm { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .alert-glass-success { background: rgba(32, 201, 151, 0.1); color: #20c997; border: 1px solid rgba(32, 201, 151, 0.2); }
    .alert-glass-danger { background: rgba(255, 36, 228, 0.1); color: #ff24e4; border: 1px solid rgba(255, 36, 228, 0.2); }

    /* Effects */
    .glass-depth { background: linear-gradient(135deg, rgba(21, 26, 35, 0.8) 0%, rgba(21, 26, 35, 0.4) 100%) !important; }
    .animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
    @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    [data-theme="light"] :root {
        --track-role-user: #475569;
        --track-status-ok: #198754;
        --track-role-admin: #0d6efd;
    }
    [data-theme="light"] .bg-surface-nav { background: #ffffff; border: 1px solid #dee2e6 !important; }
    [data-theme="light"] .bg-action-panel { background: #f8f9fa; border-color: #dee2e6; }
    [data-theme="light"] .divider-vertical { background: #dee2e6; }
    [data-theme="light"] .track-page-title { color: #1e293b; }
    [data-theme="light"] .track-page-lead { color: #475569; }
    [data-theme="light"] .glass-depth { background: #ffffff !important; border: 1px solid #dee2e6 !important; box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important; }
    [data-theme="light"] .shop-badge { background: rgba(13, 110, 253, 0.08); color: #0d6efd; }
</style>

<style>
    .letter-spacing-1 { letter-spacing: 0.1em; }
    /* Ajustes específicos de contraste para light mode si son necesarios fuera de variables */
    [data-theme="light"] .table-premium thead th {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    .btn-action-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s ease;
        background: transparent;
    }
    .btn-action-icon:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: scale(1.15);
    }
    [data-theme="light"] .btn-action-icon:hover {
        background: rgba(0, 0, 0, 0.05);
    }
    .hover-grow {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-grow:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 255, 0, 0.2) !important;
    }
</style>
