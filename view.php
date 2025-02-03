<?php
// blocks/users_courses/view.php

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/users_courses/block_users_courses.php');

// Verificar autenticación y permisos
require_login();
$context = $PAGE->context;
// debugging('context'.$context, DEBUG_DEVELOPER); // Mensaje de depuración
require_capability('moodle/site:config', $context); // Solo usuarios con permisos de administrador

// Configurar la página
$PAGE->set_url(new moodle_url('/blocks/users_courses/view.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'block_users_courses'));
$PAGE->set_heading(get_string('pluginname', 'block_users_courses'));


// Agregar el enlace en la barra de navegación
$PAGE->navbar->add(get_string('pluginname', 'block_users_courses'), new moodle_url('/blocks/users_courses/view.php'));


// Mostrar el contenido del bloque
echo $OUTPUT->header();

// Instanciar el bloque y obtener su contenido
$block = new block_users_courses();
$block->init();
echo $block->get_content()->text;

echo $OUTPUT->footer();