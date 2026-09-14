<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

if (empty($_GET['eventoid'])) { echo '<div class="alert alert-warning m-2">Falta eventoid</div>'; exit; }
$eventoid = (int)$_GET['eventoid'];
$db = new FirebirdConnection(true);

// Info de OC (status + archivo)
$info = $db->query("
  SELECT
    r.OC_STATUS,
    r.OC_FILE_ID,
    r.OC_FECHA,
    f.FILE_NOMBRE,
    f.FILE_PATH,
    f.FILE_MIME,
    f.FILE_SIZE
  FROM AMPAR_EVENTOS_REVFLOW r
  LEFT JOIN AMPAR_EVENTOS_FILES f ON f.FILE_ID = r.OC_FILE_ID
  WHERE r.EVENTO_ID = ?
", [ $eventoid ]);

$st  = $info ? (int)$info[0]['OC_STATUS'] : 0;
$txt = [0=>'–',20=>'Cargada',21=>'Rechazada',22=>'Aprobada'][$st] ?? '?';
$badge = [0=>'secondary',20=>'info',21=>'danger',22=>'success'][$st] ?? 'secondary';

$fileName = $info[0]['FILE_NOMBRE'] ?? null;
$filePath = $info[0]['FILE_PATH'] ?? null;
$fecha    = $info[0]['OC_FECHA'] ?? null;

// Comentarios
$cmts = $db->query("
  SELECT c.CREADO_EN, c.TEXTO, u.USUARIO_NOMBRE
    FROM AMPAR_EVENTOS_OC_COMENTARIOS c
    LEFT JOIN AMPAR_CAT_USUARIOS u ON u.USUARIO_ID = c.USUARIO_ID
   WHERE c.EVENTO_ID = ?
   ORDER BY c.CREADO_EN DESC
", [ $eventoid ]);

?>
<div class="container-fluid p-2">
  <div class="mb-2">
    <span class="badge badge-<?=$badge?>">OC: <?=$txt?></span>
    <?php if($fecha): ?><small class="text-muted ml-2">Actualizada: <?=$fecha?></small><?php endif; ?>
  </div>

  <?php if($filePath): ?>
    <div class="card mb-2">
      <div class="card-body py-2">
        <div><strong>Archivo:</strong> <?=$fileName?></div>
        <div><a href="../<?=$filePath?>" target="_blank" class="btn btn-sm btn-outline-primary">Ver / Descargar</a></div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-warning">No hay archivo de OC cargado.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header py-2"><strong>Comentarios</strong></div>
    <div class="card-body p-0">
      <?php if(!$cmts): ?>
        <div class="p-2 text-muted">Sin comentarios</div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach($cmts as $c): ?>
            <li class="list-group-item">
              <div class="small text-muted"><?=$c['CREADO_EN']?> — <?=$c['USUARIO_NOMBRE'] ?? 'Usuario'?></div>
              <div><?=nl2br(htmlentities($c['TEXTO']))?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
