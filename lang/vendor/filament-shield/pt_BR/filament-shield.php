<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nome',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Funções',
    'column.permissions' => 'Permissões',
    'column.updated_at' => 'Alterado em',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nome',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permissões',
    'field.select_all.name' => 'Selecionar todos',
    'field.select_all.message' => 'Habilitar todas as permissões para essa função',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Gerenciamento de Usuários',
    'nav.role.label' => 'Funções',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Função',
    'resource.label.roles' => 'Funções',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */
    'section' => 'Entidades',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Páginas',
    'custom' => 'Permissões customizadas',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Você não tem permissão para acessar',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Visualizar',
        'view_any' => 'Visualizar Qualquer',
        'create' => 'Criar',
        'update' => 'Atualizar',
        'delete' => 'Excluir',
        'delete_any' => 'Excluir Qualquer',
        'force_delete' => 'Excluir Permanentemente',
        'force_delete_any' => 'Excluir Qualquer Permanentemente',
        'restore' => 'Restaurar',
        'reorder' => 'Reordenar',
        'restore_any' => 'Restaurar Qualquer',
        'replicate' => 'Replicar',
    ],
];
