<?php 
include('config.php'); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$stmtUser = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user_data = $stmtUser->fetch(PDO::FETCH_ASSOC);

$mi_rol = $_SESSION['rol'];
$mi_cedula = $user_data['cedula']; 
$mi_nombre = $_SESSION['nombre'];
$mi_correo = $user_data['correo'];

// --- 1. LÓGICA DE MODO DE VISTA ---
$modo_vista = 'gestion'; 
if ($mi_rol == 'empleado') {
    $modo_vista = 'empleado';
} elseif (isset($_GET['modo']) && $_GET['modo'] == 'empleado') {
    $modo_vista = 'empleado';
}

// --- 2. LÓGICA DE FILTROS SQL ---
$where_panel = "WHERE 1=1"; 
$params_panel = [];
$filtro_cedula = $_GET['cedula'] ?? "";
$filtro_fecha = $_GET['fecha'] ?? "";
$filtro_nombre = $_GET['nombre_buscar'] ?? "";

if ($modo_vista == 'gestion') {
    if ($mi_rol == 'jefe') {
        $where_panel .= " AND s.correo_jefe = ?";
        $params_panel[] = $mi_correo;
    }
    
    if (!empty($filtro_cedula)) {
        $where_panel .= " AND s.cedula LIKE ?"; 
        $params_panel[] = "%$filtro_cedula%";
    }
    if (!empty($filtro_fecha)) {
        $where_panel .= " AND s.fecha_inicio = ?";
        $params_panel[] = $filtro_fecha;
    }
    if (!empty($filtro_nombre)) {
        $where_panel .= " AND s.empleado = ?";
        $params_panel[] = $filtro_nombre;
    }
}

// --- 3. LISTA DE EMPLEADOS ---
$empleados_list = [];
if ($modo_vista == 'gestion') {
    if ($mi_rol == 'admin') {
        $empleados_list = $db->query("SELECT DISTINCT nombre_completo FROM usuarios WHERE rol = 'empleado' ORDER BY nombre_completo ASC")->fetchAll();
    } elseif ($mi_rol == 'jefe') {
        $stmtLista = $db->prepare("SELECT DISTINCT empleado as nombre_completo FROM solicitudes WHERE correo_jefe = ? ORDER BY empleado ASC");
        $stmtLista->execute([$mi_correo]);
        $empleados_list = $stmtLista->fetchAll();
    }
}

function obtenerOpcionesHoras() {
    $opciones = []; $periodos = ['AM', 'PM'];
    foreach ($periodos as $p) {
        for ($h = 0; $h < 12; $h++) {
            $horaDisplay = ($h == 0) ? 12 : $h; 
            for ($m = 0; $m < 60; $m += 10) {
                $minutos = str_pad($m, 2, '0', STR_PAD_LEFT);
                $opciones[] = "$horaDisplay:$minutos $p";
            }
        }
    }
    return $opciones;
}
$listadoHoras = obtenerOpcionesHoras();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agro-Costa | Gestión de Permisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        :root { --cat-yellow: #FFCD00; --cat-black: #1A1A1A; --apple-bg: #F5F5F7; --soft-gray: #86868b; }
        body { background-color: var(--apple-bg); font-family: -apple-system, sans-serif; color: var(--cat-black); }
        .navbar { background: var(--cat-black) !important; border-bottom: 3px solid var(--cat-yellow); }
        .navbar-brand { font-weight: 700; color: var(--cat-yellow) !important; }
        .card-apple { background: #ffffff; border: none; border-radius: 22px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); overflow: hidden; }
        .btn-cat { background-color: var(--cat-black); color: var(--cat-yellow); border-radius: 12px; font-weight: 600; border: none; padding: 10px 24px; transition: 0.3s; }
        .btn-cat:hover { background-color: #000; color: #fff; }
        .form-control, .form-select, .select2-container--bootstrap-5 .select2-selection { border-radius: 12px !important; border: 1px solid #d2d2d7 !important; background-color: #fbfbfd !important; }
        .table thead th { background-color: var(--cat-black); color: var(--cat-yellow); font-size: 0.75rem; text-transform: uppercase; padding: 18px; border: none; }
        .table td { padding: 18px; border-top: 1px solid #f2f2f7; font-size: 0.85rem; }
        .badge-status { border-radius: 8px; padding: 6px 12px; font-weight: 600; }
        label { font-weight: 600; font-size: 0.75rem; color: var(--soft-gray); margin-bottom: 5px; margin-left: 5px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark sticky-top mb-4 py-2">
    <div class="container-fluid px-5">
        <a class="navbar-brand d-flex align-items-center" href="#"> AGRO-COSTA </a>
        
        <div class="d-flex align-items-center">
            <span class="text-white small me-3 d-none d-md-block">Hola, <strong><?php echo $mi_nombre; ?></strong> (<?php echo ucfirst($mi_rol); ?>)</span>
            
            <?php if ($mi_rol != 'empleado'): ?>
                <?php if ($modo_vista == 'gestion'): ?>
                    <a href="index.php?modo=empleado" class="btn btn-sm btn-light fw-bold me-2 text-dark" style="border-radius: 10px;">
                        <i class="fas fa-user-edit me-1"></i> MI PORTAL EMPLEADO
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-sm btn-warning fw-bold me-2" style="border-radius: 10px;">
                        <i class="fas fa-chart-line me-1"></i> VOLVER A GESTIÓN
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <button type="button" class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#modalClave">
                <i class="fas fa-key"></i> Clave
            </button>
            <a href="logout.php" class="btn btn-sm btn-outline-danger px-3" style="border-radius: 10px;">SALIR</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-5">
    
    <div class="mb-4">
        <?php if($modo_vista == 'empleado'): ?>
            <h4 class="fw-bold"><i class="fas fa-user-circle text-warning me-2"></i> Mis Solicitudes Personales</h4>
            <p class="text-muted small">Aquí puedes crear y ver el estado de tus propios permisos.</p>
        <?php else: ?>
            <h4 class="fw-bold"><i class="fas fa-users-cog text-warning me-2"></i> Panel de Gestión</h4>
            <p class="text-muted small">Administra las solicitudes de tu equipo.</p>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        
        <?php if($modo_vista == 'empleado'): ?>
        <div class="col-lg-4">
            <div class="card card-apple p-4">
                <h5 class="fw-bold mb-4">Nueva Solicitud</h5>
                <form action="procesar.php" method="POST" enctype="multipart/form-data" onsubmit="return validarArchivos()">
                    <input type="hidden" name="cedula" value="<?php echo $mi_cedula; ?>">
                    <input type="hidden" name="empleado" value="<?php echo $mi_nombre; ?>">
                    <input type="hidden" name="cargo" value="<?php echo $user_data['cargo']; ?>">

                    <div class="mb-3">
                        <label>TIPO DE PERMISO</label>
                        <select name="motivo" id="selectMotivo" class="form-select" onchange="validarRequerido()" required>
                            <option value="">Seleccionar...</option>
                            <option value="Permiso Remunerado">Permiso Remunerado</option>
                            <option value="Cita Médica">Cita Médica (Soporte Obligatorio)</option>
                            <option value="Compensatorio">Compensatorio (Soporte Obligatorio)</option>
                            <option value="Obligaciones Escolares">Obligaciones Escolares (Soporte Obligatorio)</option>
                            <option value="Citación Judicial - Administrativo">Citación Judicial - Administrativo (Soporte Obligatorio)</option>
                            <option value="Licencia por Luto">Licencia por Luto (Soporte Obligatorio)</option>
                            <option value="Día de la Familia">Día de la Familia</option>
                            <option value="Vacaciones">Vacaciones</option>
                            <option value="Licencia No Remunerada">Licencia No Remunerada</option>
                            <option value="Calamidad Doméstica">Calamidad Doméstica</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>JEFE (CORREO)</label>
                        <input type="email" name="correo_jefe" class="form-control" placeholder="jefe@agro-costa.com" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label>FECHA INICIO</label><input type="date" name="fecha_inicio" class="form-control" required></div>
                        <div class="col-6"><label>FECHA FIN</label><input type="date" name="fecha_fin" class="form-control" required></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label>HORA INICIO</label>
                            <select name="hora_inicio" class="form-select select2-time">
                                <option value="">Día completo</option>
                                <?php foreach ($listadoHoras as $hora): ?>
                                    <option value="<?php echo $hora; ?>"><?php echo $hora; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label>HORA FIN</label>
                            <select name="hora_fin" class="form-select select2-time">
                                <option value="">Día completo</option>
                                <?php foreach ($listadoHoras as $hora): ?>
                                    <option value="<?php echo $hora; ?>"><?php echo $hora; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>NOTAS</label>
                        <textarea name="notas" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label>SOPORTES (HASTA 5 ARCHIVOS)</label>
                        <div class="bg-light p-3 rounded border">
                            <div class="mb-2"><input type="file" name="soporte[]" class="form-control form-control-sm input-soporte"></div>
                            <div class="mb-2"><input type="file" name="soporte[]" class="form-control form-control-sm input-soporte"></div>
                            <div class="mb-2"><input type="file" name="soporte[]" class="form-control form-control-sm input-soporte"></div>
                            <div class="mb-2"><input type="file" name="soporte[]" class="form-control form-control-sm input-soporte"></div>
                            <div class="mb-2"><input type="file" name="soporte[]" class="form-control form-control-sm input-soporte"></div>
                        </div>
                        <span id="asterisco" class="text-danger small fw-bold" style="display:none;">* Soporte Requerido</span>
                        <div id="resumenArchivos" class="mt-2 small text-muted"></div>
                    </div>

                    <button type="submit" class="btn btn-cat w-100 py-3">ENVIAR SOLICITUD</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?php echo ($modo_vista == 'empleado') ? 'col-lg-8' : 'col-12'; ?>">
            
            <?php if($modo_vista == 'gestion'): ?>
            <div class="card card-apple p-4 mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label>COLABORADOR</label>
                        <select name="nombre_buscar" id="selNombre" class="form-select select2">
                            <option value="">Todos</option>
                            <?php foreach($empleados_list as $e): ?>
                                <option value="<?php echo $e['nombre_completo']; ?>" <?php if($filtro_nombre==$e['nombre_completo']) echo 'selected'; ?>><?php echo $e['nombre_completo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label>CÉDULA</label><input type="text" name="cedula" class="form-control" value="<?php echo $filtro_cedula; ?>"></div>
                    <div class="col-md-2"><label>FECHA</label><input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>"></div>
                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-cat flex-fill">BUSCAR</button>
                        <a href="index.php" class="btn btn-light border flex-fill text-center d-flex align-items-center justify-content-center fw-bold" style="border-radius:12px;">LIMPIAR</a>
                        <?php if($mi_rol == 'admin'): ?>
                            <a href="exportar.php" class="btn btn-success d-flex align-items-center justify-content-center" style="border-radius:12px; width: 50px;" title="Exportar a Excel">
                                <i class="fas fa-file-excel"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card card-apple p-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <?php if($modo_vista == 'gestion'): ?> 
                                    <th>Colaborador</th> 
                                    <th>Cédula</th>
                                <?php endif; ?>
                                <th>Motivo</th>
                                <th class="text-center" style="width: 25%;">Soportes</th>
                                <th>Fecha / Horario</th>
                                <th>Estado</th>
                                <th>Gestor</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // --- SOLUCIÓN AL PROBLEMA DE DUPLICADOS ---
                            // Eliminamos el JOIN porque 'solicitudes' ya tiene la cédula.
                            $sql_final = "SELECT s.* FROM solicitudes s ";
                            
                            if($modo_vista == 'empleado'){
                                $stmt = $db->prepare($sql_final . "WHERE s.cedula = ? ORDER BY s.id DESC");
                                $stmt->execute([$mi_cedula]);
                            } else {
                                $stmt = $db->prepare($sql_final . "$where_panel ORDER BY s.id DESC");
                                $stmt->execute($params_panel);
                            }

                            while($row = $stmt->fetch()):
                                $color = ($row['estado']=='Aprobado')?'#34C759':(($row['estado']=='Rechazado')?'#FF3B30':'#FFCC00');
                                $txt = ($row['estado']=='Pendiente')?'#000':'#fff';
                                $h_ini_val = trim($row['hora_inicio']);
                                $horario_txt = (empty($h_ini_val) || $h_ini_val == '00:00:00' || $h_ini_val == '0:00') ? "Día completo" : $row['hora_inicio']." - ".$row['hora_fin'];
                                
                                $ip_audit = $row['ip_aprobacion'] ?? 'No registrada';
                                $disp_audit = $row['info_dispositivo'] ?? 'No registrado';
                                $fecha_audit = $row['fecha_gestion'] ?? 'No registrada';
                                $fecha_envio = $row['fecha_solicitud'] ?? 'No registrada';
                                $correo_jefe_dest = $row['correo_jefe'] ?? 'No registrado';
                                
                                $archivos = [];
                                if(!empty($row['archivo_soporte'])) {
                                    $archivos = explode(',', $row['archivo_soporte']);
                                }
                            ?>
                            <tr>
                                <?php if($modo_vista == 'gestion'): ?> 
                                    <td class="fw-bold"><?php echo $row['empleado']; ?></td> 
                                    <td class="text-secondary fw-bold"><?php echo $row['cedula']; ?></td>
                                <?php endif; ?>
                                <td class="small fw-medium"><?php echo $row['motivo']; ?></td>
                                <td class="small">
                                    <?php if(count($archivos) > 0): ?>
                                        <?php foreach($archivos as $archivo): 
                                            $archivo_full = trim($archivo);
                                            $partes = explode('__', $archivo_full);
                                            $nombre_bonito = end($partes);
                                        ?>
                                            <a href="uploads/<?php echo rawurlencode($archivo_full); ?>" target="_blank" class="text-decoration-none d-block mb-1 text-truncate" style="max-width: 250px;" title="<?php echo $nombre_bonito; ?>">
                                                <i class="fas fa-paperclip text-secondary"></i> <?php echo $nombre_bonito; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?> 
                                        <span class="text-muted">-</span> 
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <strong><?php echo $row['fecha_inicio']; ?></strong> al <strong><?php echo $row['fecha_fin']; ?></strong><br>
                                    <span class="text-muted"><?php echo $horario_txt; ?></span>
                                </td>
                                <td><span class="badge badge-status" style="background-color:<?php echo $color; ?>; color:<?php echo $txt; ?>;"><?php echo $row['estado']; ?></span></td>
                                <td class="small text-muted">
                                    <?php 
                                        if(!empty($row['usuario_gestor'])) {
                                            echo '<i class="fas fa-user-check"></i> ' . $row['usuario_gestor'];
                                        } elseif ($row['estado'] != 'Pendiente') {
                                            echo 'Sistema';
                                        } else {
                                            echo '-';
                                        }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <?php if($modo_vista == 'gestion'): ?>
                                        <?php if($row['estado'] == 'Pendiente'): ?>
                                            <a href="gestionar.php?id=<?php echo $row['id']; ?>" class="btn btn-cat btn-sm py-1 px-3">GESTIONAR</a>
                                        <?php elseif(!empty($row['ip_aprobacion'])): ?>
                                            <button class="btn btn-sm btn-outline-dark" 
                                                    onclick='verAuditoria("<?php echo $ip_audit; ?>", "<?php echo htmlspecialchars($disp_audit); ?>", "<?php echo $row['estado']; ?>", "<?php echo $fecha_audit; ?>", "<?php echo $correo_jefe_dest; ?>", "<?php echo $fecha_envio; ?>")'
                                                    title="Ver Auditoría">
                                                <i class="fas fa-fingerprint"></i>
                                            </button>
                                        <?php else: ?> 
                                            <i class="fas fa-check-double text-muted small"></i> 
                                        <?php endif; ?>
                                    
                                    <?php elseif($modo_vista == 'empleado' && $row['estado'] == 'Pendiente'): ?>
                                        <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Estás seguro de que quieres eliminar esta solicitud?');" title="Eliminar Solicitud">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <i class="fas fa-lock text-muted small"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalClave" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px; border:none; overflow:hidden;">
            <div class="modal-header bg-dark text-white" style="border-bottom: 4px solid #FFCD00;">
                <h5 class="modal-title fw-bold">Cambiar Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="cambiar_clave.php" method="POST">
                    <div class="mb-3"><label class="fw-bold small text-muted">CONTRASEÑA ACTUAL</label><input type="password" name="clave_actual" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small text-muted">NUEVA CONTRASEÑA</label><input type="password" name="clave_nueva" class="form-control" required minlength="4"></div>
                    <div class="mb-4"><label class="fw-bold small text-muted">CONFIRMAR NUEVA</label><input type="password" name="clave_confirmar" class="form-control" required minlength="4"></div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">ACTUALIZAR</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAudit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border:none; overflow:hidden;">
            <div class="modal-header" style="background-color: #000; color: #FFCD00; border-bottom: 4px solid #FFCD00;">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt me-2"></i> AUDITORÍA FORENSE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="row mb-4">
                    <div class="col-6">
                         <p class="mb-1 fw-bold text-muted small">ENVIADO A (JEFE)</p>
                         <div class="bg-light p-2 rounded border small text-break">
                            <i class="fas fa-envelope text-secondary me-1"></i> <span id="auditDestino" class="fw-bold"></span>
                         </div>
                    </div>
                    <div class="col-6">
                         <p class="mb-1 fw-bold text-muted small">FECHA DE ENVÍO</p>
                         <div class="bg-light p-2 rounded border small">
                            <i class="fas fa-calendar-alt text-secondary me-1"></i> <span id="auditFechaEnvio" class="fw-bold"></span>
                         </div>
                    </div>
                </div>

                <hr>
                
                <p class="mb-1 fw-bold text-muted small">ESTADO FINAL</p>
                <h3 id="auditEstado" class="fw-bold mb-4"></h3>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded h-100 border">
                            <i class="fas fa-network-wired fa-lg text-warning mb-2"></i>
                            <p class="mb-0 small fw-bold text-muted">IP APROBACIÓN</p>
                            <span id="auditIP" class="fw-bold text-dark"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded h-100 border">
                            <i class="fas fa-clock fa-lg text-warning mb-2"></i>
                            <p class="mb-0 small fw-bold text-muted">FECHA APROBACIÓN</p>
                            <span id="auditFecha" class="fw-bold text-dark" style="font-size: 0.8rem;"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-light rounded border">
                    <i class="fas fa-laptop fa-lg text-warning mb-2"></i>
                    <p class="mb-0 small fw-bold text-muted">DISPOSITIVO GESTOR</p>
                    <h5 id="auditDispBonito" class="fw-bold text-dark mb-1"></h5>
                    <small id="auditDispRaw" class="text-muted" style="font-size: 0.65rem;"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() { 
        $('#selNombre, .select2-time').select2({ theme: 'bootstrap-5' }); 
        $('.input-soporte').on('change', function() { actualizarResumen(); });
    });

    function validarRequerido() {
        var m = document.getElementById('selectMotivo').value;
        var req = ['Cita Médica', 'Compensatorio', 'Obligaciones Escolares', 'Citación Judicial - Administrativo', 'Licencia por Luto'];
        var isReq = req.includes(m);
        document.getElementById('asterisco').style.display = isReq ? 'block' : 'none';
    }

    function actualizarResumen() {
        var inputs = document.querySelectorAll('.input-soporte');
        var lista = document.getElementById('resumenArchivos');
        lista.innerHTML = ""; 
        var ul = document.createElement('ul'); ul.style.listStyleType = "none"; ul.style.paddingLeft = "0";
        var hayArchivos = false;
        inputs.forEach(function(input, index) {
            if(input.files && input.files[0]) {
                hayArchivos = true;
                var file = input.files[0];
                var li = document.createElement('li');
                li.innerHTML = '<span class="badge bg-secondary me-2">#' + (index+1) + '</span> ' + '<i class="fas fa-paperclip text-warning me-1"></i> ' + file.name + ' <span class="text-secondary small">(' + (file.size/1024/1024).toFixed(2) + ' MB)</span>';
                ul.appendChild(li);
            }
        });
        if(hayArchivos) { lista.appendChild(ul); }
    }

    function validarArchivos() {
        var inputs = document.querySelectorAll('.input-soporte');
        var alguno = false;
        for (var i = 0; i < inputs.length; i++) {
            if(inputs[i].files && inputs[i].files[0]) {
                alguno = true;
                if (inputs[i].files[0].size > 26214400) { alert("El archivo en la casilla #" + (i+1) + " pesa más de 25MB."); return false; }
            }
        }
        var m = document.getElementById('selectMotivo').value;
        var req = ['Cita Médica', 'Compensatorio', 'Obligaciones Escolares', 'Citación Judicial - Administrativo', 'Licencia por Luto'];
        if (req.includes(m) && !alguno) { alert("Para este motivo es OBLIGATORIO subir al menos un soporte."); return false; }
        return true;
    }

    function analizarDispositivo(ua) {
        var tipo = "PC"; var icono = "fa-desktop"; 
        if (/iPhone/i.test(ua)) { tipo = "Celular (iPhone)"; icono = "fa-mobile-alt"; }
        else if (/Android/i.test(ua)) { tipo = "Celular (Android)"; icono = "fa-mobile-alt"; }
        else if (/iPad/i.test(ua) || /Tablet/i.test(ua)) { tipo = "Tablet"; icono = "fa-tablet-alt"; }
        else if (/Mobile/i.test(ua)) { tipo = "Celular Genérico"; icono = "fa-mobile-alt"; } 
        else {
            var nombre = "PC / Laptop";
            if (/Windows/i.test(ua)) nombre = "PC Windows";
            else if (/Macintosh/i.test(ua)) nombre = "Mac";
            else if (/Linux/i.test(ua)) nombre = "Linux PC";
            tipo = nombre;
        }
        var browser = "";
        if (/Edg/i.test(ua)) browser = "Edge";
        else if (/Chrome/i.test(ua) && !/Edg/i.test(ua)) browser = "Chrome";
        else if (/Safari/i.test(ua) && !/Chrome/i.test(ua)) browser = "Safari";
        else if (/Firefox/i.test(ua)) browser = "Firefox";
        return { texto: tipo + (browser ? " - " + browser : ""), icono: icono };
    }

    function verAuditoria(ip, dispositivo, estado, fecha, destinatario, fechaEnvio) {
        document.getElementById('auditIP').innerText = ip;
        document.getElementById('auditFecha').innerText = fecha ? fecha : 'Sin registro';
        document.getElementById('auditDispRaw').innerText = dispositivo;
        document.getElementById('auditDestino').innerText = destinatario ? destinatario : 'No registrado';
        document.getElementById('auditFechaEnvio').innerText = fechaEnvio ? fechaEnvio : 'No registrada';

        var elEstado = document.getElementById('auditEstado');
        elEstado.innerText = estado;
        elEstado.style.color = (estado === 'Aprobado') ? '#34C759' : '#FF3B30';

        var info = analizarDispositivo(dispositivo);
        document.getElementById('auditDispBonito').innerText = info.texto;
        
        var iconoContainer = document.querySelector('#modalAudit .fa-laptop, #modalAudit .fa-mobile-alt, #modalAudit .fa-tablet-alt, #modalAudit .fa-desktop');
        if(iconoContainer) { iconoContainer.className = "fas " + info.icono + " fa-2x text-warning mb-2"; }

        var myModal = new bootstrap.Modal(document.getElementById('modalAudit'));
        myModal.show();
    }
</script>
</body>
</html>