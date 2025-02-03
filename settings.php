<?php
// blocks/users_courses/settings.php

defined('MOODLE_INTERNAL') || die();

global $USER, $PAGE, $ADMIN;

// Obtener el contexto del sistema
// require_login();
// $context = $PAGE->context;
$context = context_system::instance(); // Usa context_system::instance() para obtener el contexto del sistema


// Verificar si el usuario tiene el rol de Gestor (rol ID = 1)
$is_manager = user_has_role_assignment($USER->id, 1, $context->id);

// Solo agregar el enlace si el usuario es Gestor
if ($is_manager) {
    // Agregar un enlace en la barra lateral de navegación
    $ADMIN->add('root', new admin_externalpage(
        'block_users_courses', // Clave única
        get_string('pluginname', 'block_users_courses'), // Nombre del enlace
        new moodle_url('/blocks/users_courses/view.php'), // URL de la página
        // 'moodle/site:config' // Permiso requerido
    ));
}