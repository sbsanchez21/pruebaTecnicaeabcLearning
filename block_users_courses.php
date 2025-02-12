<?php
// blocks/users_courses/block_users_courses.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php'); // Incluir la clase base

class block_users_courses extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_users_courses');
    }

    public function get_content() {
        global $DB, $OUTPUT, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        // Obtener el contexto del bloque
        $context = $PAGE->context;

        // Verificar si el usuario tiene el rol de Gestor (rol ID = 1)
        $is_manager = user_has_role_assignment($USER->id, 1, $context->id);

        // Si el usuario no es Gestor, mostrar mensaje de error
        if (!$is_manager) {
            $this->content = new stdClass();
            $this->content->text = get_string('nopermission', 'block_users_courses');
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Usuarios por página
        $perpage = 5;

        // Página actual
        $page = optional_param('page', 0, PARAM_INT);

        // Calcular offset
        $offset = $page * $perpage;

        // Total de usuarios
        $totalusers = $DB->count_records('user', array('deleted' => 0));

        // Usuarios para la página actual
        $users = $DB->get_records('user', array('deleted' => 0), 'lastname ASC', 'id, username, firstname, lastname', $offset, $perpage);
        if (!$users) {
            debugging('No se encontraron usuarios', DEBUG_DEVELOPER); // Mensaje de depuración
        }

        // CSS
        $this->content->text .= '<link rel="stylesheet" type="text/css" href="blocks/users_courses/styles.css">';

        // Si no hay usuarios
        if (empty($users)) {
            $this->content->text .= html_writer::tag('p', get_string('nousersfound', 'block_users_courses'));
            return $this->content;
        }

        // Tabla HTML
        $table = new html_table();
        $table->head = array(
            get_string('username', 'block_users_courses'),
            get_string('name', 'block_users_courses'),
            get_string('lastname', 'block_users_courses'),
            get_string('courses', 'block_users_courses')
        );
        $table->attributes['class'] = 'block-users-courses-table';

        foreach ($users as $user) {
            $courses = enrol_get_all_users_courses($user->id, true, 'id, fullname');
            $courselist = array();
            foreach ($courses as $course) {
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $courselist[] = html_writer::link($courseurl, format_string($course->fullname));
            }

            if (!empty($courselist)) {
                $table->data[] = array(
                    $user->username,
                    $user->firstname,
                    $user->lastname,
                    implode('<br>', $courselist)
                );
            }
        }

        // Si no hay datos
        if (empty($table->data)) {
            $this->content->text .= html_writer::tag('p', get_string('nocourses', 'block_users_courses'));
            return $this->content;
        }

        // Renderizar tabla
        $this->content->text .= html_writer::table($table);

        // Paginación
        $baseurl = new moodle_url('/blocks/users_courses/block_users_courses.php', array(
            'id' => $PAGE->context->instanceid,
            'page' => $page
        ));
        $this->content->text .= $OUTPUT->paging_bar($totalusers, $page, $perpage, $baseurl);

        // Menú de exportación
        $exportformats = array(
            'csv' => 'CSV',
            'excel' => 'Excel',
            'ods' => 'ODS',
            'json' => 'JSON',
            'html' => 'HTML'
        );

        $exporturl = new moodle_url('/blocks/users_courses/export.php', array('format' => 'FORMAT'));
        $this->content->text .= html_writer::start_tag('div', array('class' => 'export-options'));
        $this->content->text .= html_writer::tag('label', get_string('export', 'block_users_courses') . ': ');
        $this->content->text .= html_writer::select($exportformats, 'exportformat', '', array('' => 'Elegir formato'), array('onchange' => 'exportReport(this)'));
        $this->content->text .= html_writer::end_tag('div');

        // Script de exportación
        $this->content->text .= '
        <script>
        function exportReport(select) {
            var format = select.value;
            if (format) {
                var url = "' . $exporturl->out(false) . '".replace("FORMAT", format);
                window.location.href = url;
            }
        }
        </script>';

        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'site' => true, // Visible en la página principal
            'course' => true, // Visible en los cursos
            'my' => true, // Visible en el "Área personal"
        );
    }    
}