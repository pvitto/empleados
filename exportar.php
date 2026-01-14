<?php
// Forzar zona horaria correcta para evitar el Warning del servidor
date_default_timezone_set('America/Bogota');

include('config.php');

// Seguridad: Solo admin puede exportar
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

$filtro_cedula = isset($_GET['cedula']) ? $_GET['cedula'] : '';
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Consulta: Unimos solicitudes con usuarios mediante la cédula
$sql = "SELECT s.*, u.cargo 
        FROM solicitudes s 
        LEFT JOIN usuarios u ON s.cedula = u.cedula 
        WHERE 1=1";

$params = [];
if (!empty($filtro_cedula)) {
    $sql .= " AND s.cedula LIKE ?";
    $params[] = "%$filtro_cedula%";
}
if (!empty($filtro_fecha)) {
    $sql .= " AND s.fecha_inicio = ?";
    $params[] = $filtro_fecha;
}
$sql .= " ORDER BY s.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cabeceras para descarga de Excel (.xls)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Permisos_AgroCosta_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Asegurar que Excel lea bien las tildes
echo "<meta charset='UTF-8'>";
echo "<table border='1'>";
echo "<thead>
        <tr style='background-color:#FFCD00; color:#000; font-weight:bold;'>
            <th>ID</th>
            <th>Cédula</th>
            <th>Empleado</th>
            <th>Cargo</th>
            <th>Jefe</th>
            <th>Fecha Inicio</th>
            <th>Fecha Fin</th>
            <th>Hora Inicio</th>
            <th>Hora Fin</th>
            <th>Motivo</th>
            <th>Estado</th>
            <th>Notas</th>
            <th>Obs. Jefe</th>
        </tr>
      </thead><tbody>";

foreach ($resultados as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['cedula'] . "</td>";
    echo "<td>" . $row['empleado'] . "</td>";
    echo "<td>" . $row['cargo'] . "</td>";
    echo "<td>" . $row['correo_jefe'] . "</td>"; // Columna Jefe
    echo "<td>" . $row['fecha_inicio'] . "</td>";
    echo "<td>" . $row['fecha_fin'] . "</td>";
    echo "<td>" . $row['hora_inicio'] . "</td>";
    echo "<td>" . $row['hora_fin'] . "</td>";
    echo "<td>" . $row['motivo'] . "</td>";
    echo "<td>" . $row['estado'] . "</td>";
    echo "<td>" . ($row['notas'] ?? '') . "</td>";
    echo "<td>" . ($row['observacion_jefe'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
exit();
?>