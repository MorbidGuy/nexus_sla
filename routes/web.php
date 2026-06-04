<?php

declare(strict_types=1);

return [
    'GET login' => [AuthController::class, 'showLogin'],
    'POST login' => [AuthController::class, 'login'],
    'POST login/bootstrap-user' => [AuthController::class, 'bootstrapUser'],
    'POST logout' => [AuthController::class, 'logout'],

    'GET dashboard' => [DashboardController::class, 'index'],

    'GET users' => [UserController::class, 'index'],
    'GET users/create' => [UserController::class, 'create'],
    'POST users/store' => [UserController::class, 'store'],
    'GET users/edit' => [UserController::class, 'edit'],
    'POST users/update' => [UserController::class, 'update'],
    'POST users/delete' => [UserController::class, 'delete'],

    'GET demands' => [DemandController::class, 'index'],
    'GET demands/create' => [DemandController::class, 'create'],
    'POST demands/store' => [DemandController::class, 'store'],
    'GET demands/edit' => [DemandController::class, 'edit'],
    'POST demands/update' => [DemandController::class, 'update'],
    'POST demands/delete' => [DemandController::class, 'delete'],
    'POST demands/reopen' => [DemandController::class, 'reopen'],
    'POST demands/status' => [DemandController::class, 'status'],
    'GET demands/details' => [DemandController::class, 'details'],
    'GET demands/api_latest' => [DemandController::class, 'latest'],

    'GET kanban' => [KanbanController::class, 'index'],
    'POST kanban/update-status' => [KanbanController::class, 'updateStatus'],
];
