<?php
// blocks/users_courses/block_users_courses.php

class block_users_courses extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_users_courses');
    }

    public function get_content() {
        global $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Obtener todos los usuarios registrados (excluyendo usuarios eliminados)
        $users = $DB->get_records('user', array('deleted' => 0), 'lastname ASC', 'id, username, firstname, lastname');

        // Incluir el archivo CSS
        $this->content->text .= '<link rel="stylesheet" type="text/css" href="blocks/users_courses/styles.css">';

        // Verificar si hay usuarios
        if (empty($users)) {
            $this->content->text .= html_writer::tag('p', get_string('nousersfound', 'block_users_courses'));
            return $this->content;
        }

        // Generar la tabla HTML utilizando la API de Moodle
        $table = new html_table();
        $table->head = array(get_string('username', 'block_users_courses'), get_string('name', 'block_users_courses'), get_string('lastname', 'block_users_courses'), get_string('courses', 'block_users_courses'));
        $table->attributes['class'] = 'block-users-courses-table';

        foreach ($users as $user) {
            // Obtener los cursos en los que el usuario está matriculado
            $courses = enrol_get_all_users_courses($user->id, true, 'id, fullname');

            // Preparar la lista de cursos
            $courselist = array();
            foreach ($courses as $course) {
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $courselist[] = html_writer::link($courseurl, format_string($course->fullname));
            }

            // Agregar una fila a la tabla solo si el usuario está matriculado en al menos un curso
            if (!empty($courselist)) {
                $table->data[] = array(
                    $user->username, // Username
                    $user->firstname, // Nombre
                    $user->lastname,  // Apellido
                    implode('<br>', $courselist) // Lista de cursos
                );
            }
        }

        // Verificar si hay datos  para mostrar
        if (empty($table->data)) {
            $this->content->text .= html_writer::tag('p', get_string('nocourses', 'block_users_courses'));
            return $this->content;
        }

        // Renderizar la tabla utilizando la API de Moodle
        $this->content->text .= html_writer::table($table);


        // Agregar menú de exportación
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

        // Script para manejar la exportación
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
        return array('all' => true);
    }
}