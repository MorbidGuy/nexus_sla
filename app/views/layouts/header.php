<?php
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
$canManageUsers = in_array($role, ['gestor', 'manager', 'administrator', 'admin'], true);
$canViewDemands = in_array($role, ['usuario', 'demand_operator', 'gestor', 'manager', 'administrator', 'admin'], true);
$canCreateDemands = in_array($role, ['usuario', 'demand_operator', 'gestor', 'manager', 'administrator', 'admin'], true);
$currentRoute = $_GET['route'] ?? 'dashboard';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body data-route="<?= htmlspecialchars($currentRoute) ?>">
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <div class="brand d-flex justify-content-between align-items-center w-100">
                <span>Nexus <span>SLA</span></span>
                <button id="toggle-mute" class="btn btn-sm btn-link text-white p-0 opacity-75" title="Mudar som de notificação">
                    <span id="mute-icon">🔊</span>
                </button>
            </div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="index.php?route=dashboard">Dashboard</a>
                <?php if ($canManageUsers): ?>
                    <a class="nav-link <?= str_starts_with($currentRoute, 'users') ? 'active' : '' ?>" href="index.php?route=users">Usuários</a>
                <?php endif; ?>
                <?php if ($canViewDemands): ?>
                    <a class="nav-link <?= str_starts_with($currentRoute, 'demands') ? 'active' : '' ?>" href="index.php?route=demands">Demandas</a>
                    <?php if ($canCreateDemands): ?>
                        <a class="nav-link <?= $currentRoute === 'kanban' ? 'active' : '' ?>" href="index.php?route=kanban">Kanban</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <small><?= htmlspecialchars($user['email']) ?></small>
                <small><?= htmlspecialchars($role) ?></small>
                <form method="post" action="index.php?route=logout" class="mt-2">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-light">Sair</button>
                </form>
            </div>
        </aside>
    <?php endif; ?>
    <main id="main-content" class="main-content <?= $user ? '' : 'auth-content' ?>">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <?= htmlspecialchars($_SESSION['flash']) ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
