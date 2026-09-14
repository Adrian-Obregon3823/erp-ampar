<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cat    = $_POST['cat'] ?? $_GET['cat'] ?? '';
$q      = trim($_POST['q'] ?? $_GET['q'] ?? '');
$page   = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
$limit  = max(5, (int)($_POST['limit'] ?? $_GET['limit'] ?? 20));
$offset = ($page-1)*$limit;
$almacen = (int)($_POST['almacen'] ?? $_GET['almacen'] ?? 0);

try{
  $db = new FirebirdConnection();

  // Config por catálogo
  $CFG = [
    'hospitales'=>[
      'table'=>'AMPAR_CAT_HOSPITALES','pk'=>'HOSPITAL_ID','activo'=>'HOSPITAL_ACTIVO',
      // AQUÍ ESTÁ EL CAMBIO
      'fields'=>['HOSPITAL_NOMBRE','HOSPITAL_DIRECCION','HOSPITAL_MUNICIPIOID','HOSPITAL_TELEFONO','HOSPITAL_CONTACTO','HOSPITAL_ALMACEN'], 
      'search'=>['HOSPITAL_NOMBRE','HOSPITAL_CONTACTO','HOSPITAL_TELEFONO','HOSPITAL_DIRECCION']
    ],
    'medicos'=>[
      'table'=>'AMPAR_CAT_MEDICOS','pk'=>'MEDICO_ID','activo'=>'MEDICO_ACTIVO',
      'fields'=>['MEDICO_NOMBRE','MEDICO_TELEFONO','MEDICO_CORREO','MEDICO_TIPOEVENTOID','MEDICO_ALMACENID'],
      'search'=>['MEDICO_NOMBRE','MEDICO_CORREO','MEDICO_TELEFONO']
    ],
    'tipoevento'=>[
      'table'=>'AMPAR_CAT_TIPOEVENTO','pk'=>'TIPOEVENTO_ID','activo'=>'TIPOEVENTO_ACTIVO',
      'fields'=>['TIPOEVENTO_NOMBRE','TIPOEVENTO_SUBGRUPOID'],
      'search'=>['TIPOEVENTO_NOMBRE']
    ],
    'grupos'=>[
      'table'=>'AMPAR_CAT_TIPOEVENTOGRUPO','pk'=>'TIPOEVENTOGRUPO_ID','activo'=>'TIPOEVENTOGRUPO_ACTIVO',
      'fields'=>['TIPOEVENTOGRUPO_NOMBRE'],
      'search'=>['TIPOEVENTOGRUPO_NOMBRE']
    ],
    'subgrupos'=>[
      'table'=>'AMPAR_CAT_TIPOEVENTOSUBGRUPO','pk'=>'TIPOEVENTOSUBGRUPO_ID','activo'=>'TIPOEVENTOSUBGRUPO_ACTIVO',
      'fields'=>['TIPOEVENTOSUBGRUPO_NOMBRE','TIPOEVENTOSUBGRUPO_GRUPOID'],
      'search'=>['TIPOEVENTOSUBGRUPO_NOMBRE']
    ],
    'conceptossalida'=>[
      'table'=>'AMPAR_CONF_CONCEPTOSAL','pk'=>'CONCEPTOSAL_ID','activo'=>'1',
      'fields'=>['CONCEPTOSAL_NOMBRE','CONCEPTOSAL_FILTRO'],
      'search'=>['CONCEPTOSAL_NOMBRE']
    ],
    'categorias'=>[
      'table'=>'LINEAS_ARTICULOS','pk'=>'LINEA_ARTICULO_ID','activo'=>'OCULTO',
      'fields'=>['NOMBRE', 'GRUPO_LINEA_ID'],
      'search'=>['NOMBRE']
    ],
    'clientes'=>[
      'table'=>'CLIENTES','pk'=>'CLIENTE_ID','activo'=>'ESTATUS',
      'fields'=>['NOMBRE', 'CONTACTO1', 'TELEFONO', 'CORREO', 'UBICACION'],
      'search'=>['C.NOMBRE', 'C.CONTACTO1', 'D.EMAIL', 'D.TELEFONO1']
    ],
  ];

  if (!isset($CFG[$cat])) throw new Exception("Catálogo desconocido: $cat");
  $C = $CFG[$cat];

  $respond = function($ok,$data=null,$msg='') {
    echo json_encode(['ok'=>$ok,'data'=>$data,'msg'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
  };

  if ($action === 'get_options') {
      $type = $_POST['type'] ?? $_GET['type'] ?? '';
      $options = [];
      if ($type === 'monedas') {
          $options = $db->query("SELECT MONEDA_ID AS ID, NOMBRE FROM MONEDAS ORDER BY NOMBRE");
      } elseif ($type === 'condiciones_pago') {
          $options = $db->query("SELECT COND_PAGO_ID AS ID, NOMBRE FROM CONDICIONES_PAGO ORDER BY NOMBRE");
      } elseif ($type === 'tipos_clientes') {
          $options = $db->query("SELECT TIPO_CLIENTE_ID AS ID, NOMBRE FROM TIPOS_CLIENTES ORDER BY NOMBRE");
      } elseif ($type === 'cobradores') {
          $options = $db->query("SELECT COBRADOR_ID AS ID, NOMBRE FROM COBRADORES ORDER BY NOMBRE");
      } elseif ($type === 'vendedores') {
          $options = $db->query("SELECT VENDEDOR_ID AS ID, NOMBRE FROM VENDEDORES ORDER BY NOMBRE");
      } elseif ($type === 'ciudades') {
          $estado_id = (int)($_POST['estado_id'] ?? $_GET['estado_id'] ?? 0);
          if ($estado_id > 0) {
              $options = $db->query("SELECT CIUDAD_ID AS ID, NOMBRE FROM CIUDADES WHERE ESTADO_ID = ? ORDER BY NOMBRE", [$estado_id]);
          } else {
              $options = $db->query("SELECT CIUDAD_ID AS ID, NOMBRE FROM CIUDADES ORDER BY NOMBRE");
          }
      } elseif ($type === 'estados') {
          $options = $db->query("SELECT ESTADO_ID AS ID, NOMBRE FROM ESTADOS ORDER BY NOMBRE");
      }
      array_walk_recursive($options, function(&$item) {
          if (is_string($item)) {
              $item = preg_replace('/\\s+/', ' ', trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8')));
          }
      });
      echo json_encode($options);
      exit;
  }

  if ($action==='list'){

    if ($cat === 'hospitales') {
      $where = " WHERE H.HOSPITAL_ACTIVO = 1 ";
      $params = [];

      if ($q!==''){
        $like = '%'.$q.'%';
        $where .= " AND (
          UPPER(H.HOSPITAL_NOMBRE) LIKE UPPER(?)
          OR UPPER(H.HOSPITAL_DIRECCION) LIKE UPPER(?)
          OR UPPER(H.HOSPITAL_TELEFONO) LIKE UPPER(?)
          OR UPPER(H.HOSPITAL_CONTACTO) LIKE UPPER(?)
          OR UPPER(A.ALMACEN_NOMBRE) LIKE UPPER(?)
        )";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
      }

      if ($almacen > 0) {
        $where .= " AND H.HOSPITAL_ALMACEN = ? ";
        $params[] = $almacen;
      }

      $sqlCount = "
        SELECT COUNT(*) AS N
        FROM AMPAR_CAT_HOSPITALES H
        LEFT JOIN AMPAR_HIS_ALMACEN A
          ON A.ALMACEN_ID = H.HOSPITAL_ALMACEN
        $where
      ";
      $rows = $db->query($sqlCount, $params);
      $total = (int)($rows[0]['N'] ?? 0);

      $sql = "
        SELECT FIRST $limit SKIP $offset
          H.HOSPITAL_ID,
          H.HOSPITAL_NOMBRE,
          H.HOSPITAL_DIRECCION,
          H.HOSPITAL_MUNICIPIOID,
          H.HOSPITAL_TELEFONO,
          H.HOSPITAL_CONTACTO,
          H.HOSPITAL_ALMACEN,
          A.ALMACEN_NOMBRE AS HOSPITAL_ALMACEN_NOMBRE,
          H.HOSPITAL_ACTIVO
        FROM AMPAR_CAT_HOSPITALES H
        LEFT JOIN AMPAR_HIS_ALMACEN A
          ON A.ALMACEN_ID = H.HOSPITAL_ALMACEN
        $where
        ORDER BY H.HOSPITAL_ID DESC
      ";
      $items = $db->query($sql, $params) ?: [];

      $respond(true, ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    if ($cat === 'medicos') {
      $where = " WHERE M.MEDICO_ACTIVO = 1 ";
      $params = [];

      if ($q!==''){
        $like = '%'.$q.'%';
        $where .= " AND (
          UPPER(M.MEDICO_NOMBRE) LIKE UPPER(?)
          OR UPPER(M.MEDICO_CORREO) LIKE UPPER(?)
          OR UPPER(M.MEDICO_TELEFONO) LIKE UPPER(?)
        )";
        $params = array_merge($params, [$like, $like, $like]);
      }

      if ($almacen > 0) {
        $where .= " AND M.MEDICO_ALMACENID = ?";
        $params[] = $almacen;
      }

      $sqlCount = "
        SELECT COUNT(*) AS N
        FROM AMPAR_CAT_MEDICOS M
        LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO S ON S.TIPOEVENTOSUBGRUPO_ID = M.MEDICO_TIPOEVENTOID
        $where
      ";
      $rows = $db->query($sqlCount, $params);
      $total = (int)($rows[0]['N'] ?? 0);

      $sql = "
        SELECT FIRST $limit SKIP $offset
          M.MEDICO_ID,
          M.MEDICO_NOMBRE,
          M.MEDICO_TELEFONO,
          M.MEDICO_CORREO,
          M.MEDICO_TIPOEVENTOID,
          M.MEDICO_ACTIVO,
          S.TIPOEVENTOSUBGRUPO_NOMBRE AS SUBGRUPO_NOMBRE,
          A.ALMACEN_NOMBRE AS ALMACEN_NOMBRE
        FROM AMPAR_CAT_MEDICOS M
        LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO S ON S.TIPOEVENTOSUBGRUPO_ID = M.MEDICO_TIPOEVENTOID
        LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = M.MEDICO_ALMACENID
        $where
        ORDER BY M.MEDICO_ID DESC
      ";
      $items = $db->query($sql, $params) ?: [];

      $respond(true, ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    if ($cat === 'categorias') {
      $where = " WHERE COALESCE(LA.OCULTO, 'N') = 'N' ";
      $params = [];

      if ($q!==''){
        $like = '%'.$q.'%';
        $where .= " AND (UPPER(LA.NOMBRE) LIKE UPPER(?) OR UPPER(GL.NOMBRE) LIKE UPPER(?))";
        $params = [$like, $like];
      }

      $sqlCount = "
        SELECT COUNT(*) AS N
        FROM LINEAS_ARTICULOS LA
        LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
        $where
      ";
      $rows = $db->query($sqlCount, $params);
      $total = (int)($rows[0]['N'] ?? 0);

      $sql = "
        SELECT FIRST $limit SKIP $offset
          LA.LINEA_ARTICULO_ID,
          LA.NOMBRE,
          LA.GRUPO_LINEA_ID,
          GL.NOMBRE AS FAMILIA_NOMBRE,
          RLD.DIVISION_ID,
          D.DIVISION_NOMBRE
        FROM LINEAS_ARTICULOS LA
        LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
        LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
        LEFT JOIN AMPAR_CAT_DIVISION D ON D.DIVISION_ID = RLD.DIVISION_ID
        $where
        ORDER BY LA.NOMBRE ASC
      ";
      $items = $db->query($sql, $params) ?: [];

      $respond(true, ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    if ($cat === 'clientes') {
      $where = " WHERE COALESCE(C.ESTATUS, 'A') = 'A' ";
      $params = [];

      if ($q!==''){
        $like = '%'.$q.'%';
        $where .= " AND (
          UPPER(C.NOMBRE) LIKE UPPER(?)
          OR UPPER(C.CONTACTO1) LIKE UPPER(?)
          OR UPPER(D.EMAIL) LIKE UPPER(?)
          OR UPPER(D.TELEFONO1) LIKE UPPER(?)
        )";
        $params = [$like, $like, $like, $like];
      }

      $sqlCount = "
        SELECT COUNT(*) AS N
        FROM CLIENTES C
        LEFT JOIN DIRS_CLIENTES D ON D.CLIENTE_ID = C.CLIENTE_ID AND D.ES_DIR_PPAL = 'S'
        $where
      ";
      $rows = $db->query($sqlCount, $params);
      $total = (int)($rows[0]['N'] ?? 0);

      $sql = "
        SELECT FIRST $limit SKIP $offset
          C.CLIENTE_ID,
          C.NOMBRE AS NOMBRE_COMERCIAL,
          C.NOMBRE AS RAZON_SOCIAL,
          C.CONTACTO1 AS NOMBRE_CONTACTO,
          D.TELEFONO1 AS TELEFONO,
          D.EMAIL AS CORREO,
          D.CALLE,
          D.NUM_EXTERIOR,
          D.COLONIA,
          D.POBLACION,
          D.CIUDAD_ID,
          D.ESTADO_ID,
          D.CODIGO_POSTAL,
          D.RFC_CURP,
          CIUD.NOMBRE AS CIUDAD_NOMBRE,
          EST.NOMBRE AS ESTADO_NOMBRE,
          IIF(C.ESTATUS = 'A', 1, 0) AS ACTIVO
        FROM CLIENTES C
        LEFT JOIN DIRS_CLIENTES D ON D.CLIENTE_ID = C.CLIENTE_ID AND D.ES_DIR_PPAL = 'S'
        LEFT JOIN CIUDADES CIUD ON CIUD.CIUDAD_ID = D.CIUDAD_ID
        LEFT JOIN ESTADOS EST ON EST.ESTADO_ID = D.ESTADO_ID
        $where
        ORDER BY C.CLIENTE_ID DESC
      ";
      $items = $db->query($sql, $params) ?: [];

      array_walk_recursive($items, function(&$item) {
          if (is_string($item)) {
              $item = preg_replace('/\\s+/', ' ', trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8')));
          }
      });

      $respond(true, ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    $where = " WHERE {$C['activo']} = 1 ";
    $params = [];
    if ($cat === 'conceptossalida') {
        $where .= " AND CONCEPTOSAL_TIPO = 'S' ";
    }
    
    if ($q!==''){
      $like = '%'.$q.'%';
      $where .= " AND (".implode(' OR ', array_map(fn($f)=>"UPPER($f) LIKE UPPER(?)",$C['search'])).")";
      foreach($C['search'] as $_){ $params[]=$like; }
    }

    $sqlCount = "SELECT COUNT(*) AS N FROM {$C['table']} $where";
    $rows = $db->query($sqlCount, $params);
    $total = (int)($rows[0]['N'] ?? 0);

    $sql = "SELECT FIRST $limit SKIP $offset * FROM {$C['table']} $where ORDER BY {$C['pk']} DESC";
    $items = $db->query($sql, $params) ?: [];

    array_walk_recursive($items, function(&$item) {
        if (is_string($item)) {
            $item = preg_replace('/\\s+/', ' ', trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8')));
        }
    });

    $respond(true, ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit]);
  }
  elseif ($action==='get'){
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id<=0) throw new Exception("ID inválido");
    $sql = "SELECT * FROM {$C['table']} WHERE {$C['pk']} = ?";
    $item = $db->query($sql, [$id]);
    if(!$item) $respond(false,null,'No encontrado');
    
    $data = $item[0];
    // Eliminado el manejo de hospitales para medicos
    
    if ($cat === 'categorias') {
      $rld = $db->query("SELECT DIVISION_ID FROM AMPAR_REL_LINEA_DIVISION WHERE LINEA_ARTICULO_ID = ?", [$id]);
      $data['DIVISION_ID'] = $rld ? $rld[0]['DIVISION_ID'] : null;
    }

    if ($cat === 'clientes') {
      $dir_rows = $db->query("SELECT TELEFONO1, EMAIL, CALLE, NUM_EXTERIOR, COLONIA, POBLACION, CIUDAD_ID, ESTADO_ID, CODIGO_POSTAL, RFC_CURP FROM DIRS_CLIENTES WHERE CLIENTE_ID = ? AND ES_DIR_PPAL = 'S'", [$id]);
      if ($dir_rows && count($dir_rows) > 0) {
          $data['TELEFONO'] = $dir_rows[0]['TELEFONO1'];
          $data['CORREO'] = $dir_rows[0]['EMAIL'];
          $data['UBICACION'] = $dir_rows[0]['CALLE'];
          $data['CALLE'] = $dir_rows[0]['CALLE'];
          $data['NUM_EXTERIOR'] = $dir_rows[0]['NUM_EXTERIOR'];
          $data['COLONIA'] = $dir_rows[0]['COLONIA'];
          $data['POBLACION'] = $dir_rows[0]['POBLACION'];
          $data['CIUDAD_ID'] = $dir_rows[0]['CIUDAD_ID'];
          $data['ESTADO_ID'] = $dir_rows[0]['ESTADO_ID'];
          $data['CODIGO_POSTAL'] = $dir_rows[0]['CODIGO_POSTAL'];
          $data['RFC_CURP'] = $dir_rows[0]['RFC_CURP'];
      }
      $data['NOMBRE_COMERCIAL'] = $data['NOMBRE'];
      $data['NOMBRE_CONTACTO'] = $data['CONTACTO1'];
      // Populate fields for selects
      $data['MONEDA_ID'] = $data['MONEDA_ID'];
      $data['COND_PAGO_ID'] = $data['COND_PAGO_ID'];
      $data['TIPO_CLIENTE_ID'] = $data['TIPO_CLIENTE_ID'];
      $data['COBRADOR_ID'] = $data['COBRADOR_ID'];
      $data['VENDEDOR_ID'] = $data['VENDEDOR_ID'];
    }
    
    array_walk_recursive($data, function(&$item) {
        if (is_string($item)) {
            $item = preg_replace('/\\s+/', ' ', trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8')));
        }
    });

    $respond(true, $data);
  }
  elseif ($action==='save'){
    $id = (int)($_POST['id'] ?? 0);

    // recolectar payload
    $payload = [];
    foreach($C['fields'] as $f){
      // Nota: convierte '' a NULL en campos numéricos si quieres
      $v = $_POST[$f] ?? null;
      if (is_array($v)) {
          $v = implode(',', $v);
      }
      $payload[$f] = ($v === '') ? null : $v;
    }

    if ($cat === 'conceptossalida') {
      $payload['CONCEPTOSAL_TIPO'] = 'S';
    }

    if ($cat === 'clientes') {
        $nombre = $_POST['NOMBRE_COMERCIAL'] ?? '';
        $contacto = $_POST['NOMBRE_CONTACTO'] ?? '';
        $tel = $_POST['TELEFONO'] ?? '';
        $correo = $_POST['CORREO'] ?? '';
        
        // Location fields
        $calle = $_POST['CALLE'] ?? '';
        $num_exterior = $_POST['NUM_EXTERIOR'] ?? '';
        $colonia = $_POST['COLONIA'] ?? '';
        $poblacion = $_POST['POBLACION'] ?? '';
        $ciudad_id = !empty($_POST['CIUDAD_ID']) ? (int)$_POST['CIUDAD_ID'] : null;
        $estado_id = !empty($_POST['ESTADO_ID']) ? (int)$_POST['ESTADO_ID'] : null;
        $codigo_postal = $_POST['CODIGO_POSTAL'] ?? '';
        $rfc_curp = $_POST['RFC_CURP'] ?? '';
        
        $moneda = $_POST['MONEDA_ID'] ?? 1;
        $cond_pago = $_POST['COND_PAGO_ID'] ?? 2510;
        $tipo_cliente = $_POST['TIPO_CLIENTE_ID'] ?? 2530;
        $cobrador = $_POST['COBRADOR_ID'] ?? 8825;
        $vendedor = $_POST['VENDEDOR_ID'] ?? 35611;

        if ($id > 0) {
            $db->execute("UPDATE CLIENTES SET NOMBRE = ?, CONTACTO1 = ?, MONEDA_ID = ?, COND_PAGO_ID = ?, TIPO_CLIENTE_ID = ?, COBRADOR_ID = ?, VENDEDOR_ID = ? WHERE CLIENTE_ID = ?", 
                [$nombre, $contacto, $moneda, $cond_pago, $tipo_cliente, $cobrador, $vendedor, $id]);
            
            $dir_rows = $db->query("SELECT DIR_CLI_ID FROM DIRS_CLIENTES WHERE CLIENTE_ID = ? AND ES_DIR_PPAL = 'S'", [$id]);
            if ($dir_rows && count($dir_rows) > 0) {
                $db->execute("UPDATE DIRS_CLIENTES SET TELEFONO1 = ?, EMAIL = ?, CALLE = ?, NUM_EXTERIOR = ?, COLONIA = ?, POBLACION = ?, CIUDAD_ID = ?, ESTADO_ID = ?, CODIGO_POSTAL = ?, RFC_CURP = ? WHERE CLIENTE_ID = ? AND ES_DIR_PPAL = 'S'", 
                [$tel, $correo, $calle, $num_exterior, $colonia, $poblacion, $ciudad_id, $estado_id, $codigo_postal, $rfc_curp, $id]);
            } else {
                $sqlMaxDir = "SELECT MAX(DIR_CLI_ID) AS MAX_ID FROM DIRS_CLIENTES";
                $resMaxDir = $db->query($sqlMaxDir);
                $new_dir_id = ($resMaxDir[0]['MAX_ID'] ?? 0) + 1;
                $db->execute("INSERT INTO DIRS_CLIENTES (DIR_CLI_ID, CLIENTE_ID, TELEFONO1, EMAIL, CALLE, NUM_EXTERIOR, COLONIA, POBLACION, CIUDAD_ID, ESTADO_ID, CODIGO_POSTAL, RFC_CURP, ES_DIR_PPAL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'S')", 
                [$new_dir_id, $id, $tel, $correo, $calle, $num_exterior, $colonia, $poblacion, $ciudad_id, $estado_id, $codigo_postal, $rfc_curp]);
            }
            $respond(true, null, 'Cliente actualizado');
        } else {
            $sqlMaxId = "SELECT MAX(CLIENTE_ID) AS MAX_ID FROM CLIENTES";
            $resMax = $db->query($sqlMaxId);
            $new_id = ($resMax[0]['MAX_ID'] ?? 0) + 1;
            
            $db->execute("INSERT INTO CLIENTES (CLIENTE_ID, NOMBRE, CONTACTO1, ESTATUS, MONEDA_ID, COND_PAGO_ID, TIPO_CLIENTE_ID, COBRADOR_ID, VENDEDOR_ID, COBRAR_IMPUESTOS, RETIENE_IMPUESTOS, SUJETO_IEPS, GENERAR_INTERESES, EMITIR_EDOCTA) VALUES (?, ?, ?, 'A', ?, ?, ?, ?, ?, 'S', 'N', 'N', 'S', 'S')", 
                [$new_id, $nombre, $contacto, $moneda, $cond_pago, $tipo_cliente, $cobrador, $vendedor]);
            
            $sqlMaxDir = "SELECT MAX(DIR_CLI_ID) AS MAX_ID FROM DIRS_CLIENTES";
            $resMaxDir = $db->query($sqlMaxDir);
            $new_dir_id = ($resMaxDir[0]['MAX_ID'] ?? 0) + 1;
            
            $db->execute("INSERT INTO DIRS_CLIENTES (DIR_CLI_ID, CLIENTE_ID, TELEFONO1, EMAIL, CALLE, NUM_EXTERIOR, COLONIA, POBLACION, CIUDAD_ID, ESTADO_ID, CODIGO_POSTAL, RFC_CURP, ES_DIR_PPAL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'S')", 
            [$new_dir_id, $new_id, $tel, $correo, $calle, $num_exterior, $colonia, $poblacion, $ciudad_id, $estado_id, $codigo_postal, $rfc_curp]);
            
            $respond(true, ['CLIENTE_ID' => $new_id], 'Cliente creado');
        }
        exit;
    }

    if ($id>0){
      // UPDATE
      if ($cat === 'categorias') {
          $division_id = $_POST['DIVISION_ID'] ?? null;
          $grupo_linea_id = $_POST['GRUPO_LINEA_ID'] ?? null;
          $nombre = $_POST['NOMBRE'] ?? null;
          
          $db->execute("UPDATE LINEAS_ARTICULOS SET GRUPO_LINEA_ID = ?, NOMBRE = ? WHERE LINEA_ARTICULO_ID = ?", [$grupo_linea_id, $nombre, $id]);

          $db->execute("DELETE FROM AMPAR_REL_LINEA_DIVISION WHERE LINEA_ARTICULO_ID = ?", [$id]);
          if (!empty($division_id)) {
              $db->execute("INSERT INTO AMPAR_REL_LINEA_DIVISION (LINEA_ARTICULO_ID, DIVISION_ID) VALUES (?, ?)", [$id, $division_id]);
          }
          $respond(true, null, 'Categoría actualizada');
      } else {
          $sets = [];
          $vals = [];
          foreach($payload as $k=>$v){ $sets[]="$k=?"; $vals[]=$v; }
          $vals[] = $id;
          $sql = "UPDATE {$C['table']} SET ".implode(',', $sets)." WHERE {$C['pk']} = ?";
          $db->execute($sql, $vals);
          
          // Eliminado manejo de hospitales
          $respond(true, null, 'Actualizado');
      }
    }else{
      // INSERT
      if ($cat === 'categorias') {
          $sqlMaxId = "SELECT MAX(LINEA_ARTICULO_ID) AS MAX_ID FROM LINEAS_ARTICULOS WHERE LINEA_ARTICULO_ID BETWEEN 29701 AND 29799";
          $resMax = $db->query($sqlMaxId);
          $nuevo_id = 29701;
          if (is_array($resMax) && isset($resMax[0]['MAX_ID']) && $resMax[0]['MAX_ID'] !== null) {
              $nuevo_id = $resMax[0]['MAX_ID'] + 1;
          }
          if ($nuevo_id > 29799) {
              $respond(false, null, 'Límite de IDs web (29799) alcanzado para categorías.');
              exit;
          }
          $payload['LINEA_ARTICULO_ID'] = $nuevo_id;
      }

      $cols = implode(',', array_keys($payload));
      $qs = implode(',', array_fill(0, count($payload), '?'));
      $sql = "INSERT INTO {$C['table']} ($cols) VALUES ($qs)";
      $new_id = $db->executeconreturning($sql, $C['pk'], array_values($payload));
      
      // Eliminado manejo de hospitales

      if ($cat === 'categorias' && $new_id) {
          $division_id = $_POST['DIVISION_ID'] ?? null;
          if (!empty($division_id)) {
              $db->execute("INSERT INTO AMPAR_REL_LINEA_DIVISION (LINEA_ARTICULO_ID, DIVISION_ID) VALUES (?, ?)", [$new_id, $division_id]);
          }
      }
      
      $respond(true, [$C['pk'] => $new_id], 'Creado');
    }
  }
  elseif ($action==='set_activo'){
    $id = (int)($_POST['id'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 1);
    if (!in_array($activo,[0,1],true)) $activo=1;
    if ($id<=0) throw new Exception("ID inválido");
    if ($C['activo'] === '1') {
      $respond(true, null, 'Operación no soportada para este catálogo');
    }
    $val = $activo;
    if ($cat === 'categorias' && $C['activo'] === 'OCULTO') {
        $val = $activo ? 'N' : 'S';
    }
    if ($cat === 'clientes' && $C['activo'] === 'ESTATUS') {
        $val = $activo ? 'A' : 'B';
    }
    $sql = "UPDATE {$C['table']} SET {$C['activo']}=? WHERE {$C['pk']}=?";
    $db->execute($sql, [$val, $id]);
    $respond(true, null, ($activo ? 'Activado' : 'Desactivado'));
  }
  else{
    throw new Exception("Acción inválida");
  }

}catch(Throwable $e){
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}