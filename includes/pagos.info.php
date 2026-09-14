<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

if (empty($_GET['eventoid'])) { echo '<div class="alert alert-warning m-2">Falta eventoid</div>'; exit; }
$eventoid = (int)$_GET['eventoid'];

$db = new FirebirdConnection(true);

// Totales y estado
$ev   = $db->query("SELECT EVENTO_PRESUPUESTOPARTICULAR FROM AMPAR_EVENTOS WHERE EVENTO_ID=?",[$eventoid]);
$pres = $ev ? (float)$ev[0]['EVENTO_PRESUPUESTOPARTICULAR'] : 0;

$cargos = $db->query("
  SELECT c.CARGO_ID, c.CREADO_EN, c.MONTO, c.CONCEPTO,
         f.FILE_NOMBRE, f.FILE_PATH
    FROM AMPAR_EVENTOS_CARGOS c
    LEFT JOIN AMPAR_EVENTOS_FILES f ON f.FILE_ID = c.FILE_ID
   WHERE c.EVENTO_ID = ?
   ORDER BY c.CREADO_EN DESC
",[$eventoid]);

$total = 0;
foreach ($cargos?:[] as $c){ $total += (float)$c['MONTO']; }

$rf = $db->query("SELECT PAGO_STATUS, REV_STATUS FROM AMPAR_EVENTOS_REVFLOW WHERE EVENTO_ID=?",[$eventoid]);
$pst = $rf ? (int)$rf[0]['PAGO_STATUS'] : 0;
$rst = $rf ? (int)$rf[0]['REV_STATUS']  : 0;

$txt = [0=>'–',40=>'Abonos',41=>'Cubierto',42=>'Validado'][$pst] ?? '?';
$cls = [0=>'secondary',40=>'info',41=>'warning',42=>'success'][$pst] ?? 'secondary';

?>
<input type="hidden" id="pagos_status_val" value="<?=$pst?>">
<input type="hidden" id="pagos_rev_val"    value="<?=$rst?>">

<div class="container-fluid p-2">
  <div class="mb-2">
    <span class="badge badge-<?=$cls?>">Pagos: <?=$txt?></span>
    <span class="ml-2">Total: <strong>$<?=number_format($total,2)?></strong> / Presupuesto: <strong>$<?=number_format($pres,2)?></strong></span>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th style="width: 180px;">Fecha</th>
          <th style="width: 120px;">Monto</th>
          <th>Concepto</th>
          <th style="width: 1%;">Comprobante</th>
        </tr>
      </thead>
      <tbody>
      <?php if(!$cargos): ?>
        <tr><td colspan="4" class="text-muted">Sin pagos capturados</td></tr>
      <?php else: foreach ($cargos as $c):
        $url = $c['FILE_PATH'] ? '../'.ltrim(str_replace('\\','/',$c['FILE_PATH']),'/') : null;
      ?>
        <tr>
          <td><?=$c['CREADO_EN']?></td>
          <td>$<?=number_format((float)$c['MONTO'],2)?></td>
          <td><?=htmlentities($c['CONCEPTO'] ?? '')?></td>
          <td>
            <?php if($url): ?>
              <a class="btn btn-xs btn-outline-primary" href="<?=$url?>" target="_blank">Ver</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    // Comentarios
    $cmts = $db->query("
      SELECT p.CREADO_EN, p.TEXTO, u.USUARIO_NOMBRE
        FROM AMPAR_EVENTOS_PAGO_COMENTARIOS p
        LEFT JOIN AMPAR_CAT_USUARIOS u ON u.USUARIO_ID = p.USUARIO_ID
       WHERE p.EVENTO_ID = ?
       ORDER BY p.CREADO_EN DESC
    ", [ $eventoid ]);
  ?>
  <div class="card">
    <div class="card-header py-2"><strong>Comentarios</strong></div>
    <div class="card-body p-0">
    <?php if(!$cmts): ?>
      <div class="p-2 text-muted">Sin comentarios</div>
    <?php else: ?>
      <ul class="list-group list-group-flush">
      <?php foreach($cmts as $c): ?>
        <li class="list-group-item">
          <div class="small text-muted"><?=$c['CREADO_EN']?> — <?=htmlentities($c['USUARIO_NOMBRE'] ?? 'Usuario')?></div>
          <div><?=nl2br(htmlentities($c['TEXTO']))?></div>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    </div>
  </div>
</div>
