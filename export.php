<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// blocks/users_courses/export.php
ob_start(); // Inicia el buffer
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once(__DIR__ . '/../../config.php');
require_login();
// Cargar PhpSpreadsheet
require_once($CFG->dirroot . '/vendor/autoload.php'); 
// debugging('CFG->dirroot', $CFG->dirroot, DEBUG_DEVELOPER);
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;

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


    
            
    case 'ods':
        try {
            if (!extension_loaded('zip')) {
                die('La extensión ZIP no está habilitada en PHP.');
            }
    
            // Crear un nuevo documento
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
    
            // Encabezados
            $sheet->setCellValue('A1', 'Username');
            $sheet->setCellValue('B1', 'Nombre');
            $sheet->setCellValue('C1', 'Apellido');
            $sheet->setCellValue('D1', 'Cursos');
    
            // Llenar datos
            $rowNumber = 2;
            foreach ($data as $row) {
                $sheet->setCellValue('A' . $rowNumber, $row[0]);
                $sheet->setCellValue('B' . $rowNumber, $row[1]);
                $sheet->setCellValue('C' . $rowNumber, $row[2]);
                $sheet->setCellValue('D' . $rowNumber, $row[3]);
                $rowNumber++;
            }
    
            // Configurar headers y guardar
            ob_end_clean();
            header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
            header('Content-Disposition: attachment; filename="reporte_usuarios_cursos.ods"');
    
            // Crear el archivo ODS
            $writer = new Ods($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            die('Error al generar ODS: ' . $e->getMessage());
        }
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