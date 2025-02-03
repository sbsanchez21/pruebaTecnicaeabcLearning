<?php
// blocks/users_courses/export.php

require_once(__DIR__ . '/../../config.php');
require_login();

// Establecer el contexto de la página
$PAGE->set_context(context_system::instance());

$format = optional_param('format', '', PARAM_ALPHA); // Formato de exportación (csv, excel, ods, json, html)

// Obtener los datos del reporte
global $DB;
$users = $DB->get_records('user', array('deleted' => 0), 'lastname ASC', 'id, username, firstname, lastname');
$data = array();

foreach ($users as $user) {
    $courses = enrol_get_all_users_courses($user->id, true, 'id, fullname');
    $courselist = array();
    foreach ($courses as $course) {
        $courselist[] = format_string($course->fullname);
    }
    if (!empty($courselist)) {
        $data[] = array(
            $user->username,
            $user->firstname,
            $user->lastname,
            implode(', ', $courselist)
        );
    }
}

// Generar el archivo según el formato seleccionado
switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Username', 'Nombre', 'Apellido', 'Cursos'));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        break;

    case 'excel':
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.xls"');
    
        // Abrir el buffer de salida
        $output = fopen('php://output', 'w');
    
        // Escribir la fila de encabezados
        fputcsv($output, array('Username', 'Nombre', 'Apellido', 'Cursos'), "\t");
    
        // Escribir los datos
        foreach ($data as $row) {
            fputcsv($output, $row, "\t");
        }
    
        // Cerrar el buffer
        fclose($output);
        break;
    // case 'excel':
    //     header('Content-Type: application/vnd.ms-excel');
    //     header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.xls"');
    
    //     // Abrir el buffer de salida
    //     $output = fopen('php://output', 'w');
    
    //     // Escribir la fila de encabezados
    //     fputcsv($output, array('Username', 'Nombre', 'Apellido', 'Cursos'), "\t");
    
    //     // Escribir los datos
    //     foreach ($data as $row) {
    //         fputcsv($output, $row, "\t");
    //     }
    
    //     // Cerrar el buffer
    //     fclose($output);
    //     break;
        
        
    case 'ods':
        // Requiere una biblioteca externa para generar archivos ODS
        break;

    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        break;

    case 'html':
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.html"');
        echo "<table border='1'>";
        echo "<tr><th>Username</th><th>Nombre</th><th>Apellido</th><th>Cursos</th></tr>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        break;

    default:
        die('Formato no válido.');
}