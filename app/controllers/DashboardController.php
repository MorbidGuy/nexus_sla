<?php

declare(strict_types=1);

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->auth();
        $demand = new Demand();
        $user = new User();
        $role = $this->userRole();

        $view = match ($role) {
            'administrator', 'admin' => 'dashboard/gestor',
            'rh', 'user_manager' => 'dashboard/rh',
            'usuario', 'demand_operator' => 'dashboard/usuario',
            'gestor', 'manager' => 'dashboard/gestor',
            default => 'dashboard/index',
        };

        $currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
        $selectedDashboardUser = null;
        $selectedDashboardStats = null;
        $selectedDashboardDemands = [];
        $dashboardUserId = (int) ($_GET['user_id'] ?? 0);

        if (in_array($role, ['gestor', 'manager', 'administrator', 'admin'], true) && $dashboardUserId > 0) {
            $selectedDashboardUser = $user->find($dashboardUserId);

            if ($selectedDashboardUser && (int) $selectedDashboardUser['active'] === 1) {
                $selectedDashboardStats = $demand->statsForUser($dashboardUserId);
                $selectedDashboardDemands = $demand->assignedTo($dashboardUserId);
            } else {
                $selectedDashboardUser = null;
            }
        }

        $this->view($view, [
            'title' => 'Dashboard',
            'roleLabel' => $this->roleLabel(),
            'stats' => in_array($role, ['usuario', 'demand_operator'], true) ? $demand->statsForUser($currentUserId) : $demand->stats(),
            'recentDemands' => in_array($role, ['usuario', 'demand_operator'], true) ? $demand->assignedTo($currentUserId) : $demand->recent(),
            'users' => $user->all(),
            'userStats' => $user->statsByRole(),
            'dashboardTargets' => $user->dashboardTargets(),
            'selectedDashboardUser' => $selectedDashboardUser,
            'selectedDashboardStats' => $selectedDashboardStats,
            'selectedDashboardDemands' => $selectedDashboardDemands,
            'storageStats' => Database::storageStats(),
            'tableStats' => Database::tableStats(),
            'diskStats' => Database::diskStats(),
            'csrfField' => $this->csrfField(),
        ]);
    }
}
