<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase de Permisos
*********************************************************************************
*/
?>
<?php
class Permisos
{

    function getMenuUsuario($usuarioId)
    {
        $db = new FirebirdConnection();

        $sql = "
                SELECT M.MENU_ID, M.MENU_NOMBRE, M.MENU_URL, M.MENU_PADREID, M.MENU_NIVEL, M.MENU_ICONO
                FROM AMPAR_CONF_MENU M
                INNER JOIN AMPAR_CAT_PERFILMENU PM ON PM.PERFILMENU_MENUID = M.MENU_ID
                INNER JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_PERFILID = PM.PERFILMENU_PERFILID
                WHERE UP.USUARIOP_USUARIOID = ?
                ORDER BY M.MENU_PADREID, M.MENU_ORDEN
            ";

        $menus = $db->query($sql, [$usuarioId]);
        $db->close();

        if (!$menus) return [];

        $menuTree = [];
        $refs = [];

        // Crear referencia de menús
        foreach ($menus as $menu) {
            $menu['children'] = [];
            $refs[$menu['MENU_ID']] = $menu;
            if (!$menu['MENU_PADREID']) {
                $menuTree[$menu['MENU_ID']] = &$refs[$menu['MENU_ID']];
            }
        }

        // Asignar submenús
        foreach ($refs as $id => $menu) {
            if ($menu['MENU_PADREID']) {
                $refs[$menu['MENU_PADREID']]['children'][] = &$refs[$id];
            }
        }

        return $menuTree;
    }

    function renderSidebar($menuTree)
    {
        echo '<nav class="sidebar sidebar-offcanvas" id="sidebar"><ul class="nav">';

        foreach ($menuTree as $menu) {
            // Categoría
            if (empty($menu['MENU_URL']) && count($menu['children']) > 0) {
                echo '<li class="nav-item nav-category">' . $menu['MENU_NOMBRE'] . '</li>';
                foreach ($menu['children'] as $child) {
                    $this->renderMenuItem($child);
                }
            } else {
                $this->renderMenuItem($menu);
            }
        }

        echo '</ul></nav>';
    }

    function renderMenuItem($menu)
    {
        $currentUrl = basename($_SERVER['PHP_SELF']);

        if (count($menu['children']) > 0) {
            // Menú con submenús (nivel 1 con hijos nivel 2)
            $hasUrl = !empty($menu['MENU_URL']);

            $isExpanded = false;
            $isSelfActive = false;
            if ($hasUrl && strpos($menu['MENU_URL'], $currentUrl) !== false) {
                $isExpanded = true;
                $isSelfActive = true;
            }
            foreach ($menu['children'] as $child) {
                if (!empty($child['MENU_URL']) && strpos($child['MENU_URL'], $currentUrl) !== false) {
                    $isExpanded = true;
                }
            }

            $collapseClass = $isExpanded ? 'collapse show' : 'collapse';
            $ariaExpanded = $isExpanded ? 'true' : 'false';

            $href = '#menu' . $menu['MENU_ID'];

            $onClick = '';
            if ($hasUrl && !$isSelfActive) {
                // Si tiene URL y no estamos en su página, forzamos navegación y que cargue abierto
                $onClick = ' onclick="window.location.href=\'' . $menu['MENU_URL'] . '\';"';
            }

            echo '<li class="nav-item">';
            echo '<a class="nav-link" data-toggle="collapse" href="' . $href . '" aria-expanded="' . $ariaExpanded . '"' . $onClick . '>';
            echo '<i class="menu-icon mdi ' . $menu['MENU_ICONO'] . '"></i>';
            echo '<span class="menu-title">' . $menu['MENU_NOMBRE'] . '</span>';
            echo '<i class="menu-arrow"></i>';
            echo '</a>';
            echo '<div class="' . $collapseClass . '" id="menu' . $menu['MENU_ID'] . '"><ul class="nav flex-column sub-menu">';
            foreach ($menu['children'] as $child) {
                // Aquí salen los de nivel 2 con la estructura original exacta
                echo '<li class="nav-item">';
                echo '<a class="nav-link" href="' . $child['MENU_URL'] . '">';
                echo '<i class="menu-icon mdi ' . $child['MENU_ICONO'] . '"></i>';
                echo '<span class="menu-title">' . $child['MENU_NOMBRE'] . '</span>';
                echo '</a></li>';
            }
            echo '</ul></div></li>';
        } else {
            // Menú sin submenús (nivel 1 normal)
            echo '<li class="nav-item">';
            echo '<a class="nav-link" href="' . $menu['MENU_URL'] . '">';
            echo '<i class="menu-icon mdi ' . $menu['MENU_ICONO'] . '"></i>';
            echo '<span class="menu-title">' . $menu['MENU_NOMBRE'] . '</span>';
            echo '</a></li>';
        }
    }

    function getperfiles()
    {
        $db = new FirebirdConnection();
        $sql = "
                SELECT * FROM AMPAR_CAT_PERFILES
            ";

        $res = $db->query($sql);
        $db->close();

        return $res;
    }

    function getmenus($perfilid)
    {
        $db = new FirebirdConnection();

        // Obtener todos los menús
        $sql1 = "SELECT * FROM AMPAR_CONF_MENU ORDER BY MENU_PADREID, MENU_ORDEN";
        $menus = $db->query($sql1);

        // Obtener menús asignados a este perfil
        $sql2 = "SELECT PERFILMENU_MENUID FROM AMPAR_CAT_PERFILMENU WHERE PERFILMENU_PERFILID=?";
        $asignados = $db->query($sql2, [$perfilid]);

        // Asegurarse de que sea un array
        if (!is_array($asignados)) $asignados = [];

        $asignadosIds = array_column($asignados, 'PERFILMENU_MENUID');

        $db->close();

        $tree = [];
        $refs = [];

        foreach ($menus as $m) {
            $m['children'] = [];
            $m['checked'] = in_array($m['MENU_ID'], $asignadosIds);
            $refs[$m['MENU_ID']] = $m;
            if (!$m['MENU_PADREID']) $tree[$m['MENU_ID']] = &$refs[$m['MENU_ID']];
        }

        foreach ($refs as $id => $m) {
            if ($m['MENU_PADREID']) {
                $refs[$m['MENU_PADREID']]['children'][] = &$refs[$id];
            }
        }

        return $tree;
    }


    function getusuarios()
    {
        $db = new FirebirdConnection();
        $sql = "
                SELECT * FROM AMPAR_CAT_USUARIOS
            ";

        $res = $db->query($sql);
        $db->close();

        return $res;
    }

    function updateperfilmenu($perfil, $menus)
    {
        $db = new FirebirdConnection();

        // Limpiar menús previos
        $sql = "DELETE FROM AMPAR_CAT_PERFILMENU WHERE PERFILMENU_PERFILID=?";
        $db->execute($sql, [$perfil]);

        // Insertar los nuevos si existen
        if (!empty($menus)) {
            $sql2 = "INSERT INTO AMPAR_CAT_PERFILMENU (PERFILMENU_PERFILID, PERFILMENU_MENUID) VALUES (?, ?)";
            foreach ($menus as $m) {
                $db->execute($sql2, [$perfil, $m]);
            }
        }

        $db->close();
    }

    function crearperfil($nombre)
    {
        $db = new FirebirdConnection();

        // Alta en TIPO
        $resTipo = $db->query('SELECT MAX(USUARIOTIPO_ID) AS MAXIMO FROM AMPAR_CONF_USUARIOSTIPO');
        $idTipo = intval($resTipo[0]['MAXIMO'] ?? 0) + 1;
        $sqlTipo = "INSERT INTO AMPAR_CONF_USUARIOSTIPO (USUARIOTIPO_ID, USUARIOTIPO_NOMBRE) VALUES (?, ?)";
        $db->execute($sqlTipo, [$idTipo, $nombre]);

        // Alta en PERFIL
        $resPerfil = $db->query('SELECT MAX(PERFIL_ID) AS MAXIMO FROM AMPAR_CAT_PERFILES');
        $idPerfil = intval($resPerfil[0]['MAXIMO'] ?? 0) + 1;
        $sqlPerfil = "INSERT INTO AMPAR_CAT_PERFILES (PERFIL_ID, PERFIL_NOMBRE) VALUES (?, ?)";
        $db->execute($sqlPerfil, [$idPerfil, $nombre]);

        $db->close();
    }

    function asignarperfilusuario($usuario_id, $perfil_id)
    {
        $db = new FirebirdConnection();
        // Limpiar perfil previo o simplemente agregar? Depende si un usuario puede tener varios.
        // Asumiendo que puede tener varios, solo insertamos si no existe:
        $check = clone $db;
        $existe = $check->query("SELECT * FROM AMPAR_CAT_USUARIOSPERFILES WHERE USUARIOP_USUARIOID = ? AND USUARIOP_PERFILID = ?", [$usuario_id, $perfil_id]);
        if (empty($existe)) {
            $sql = "INSERT INTO AMPAR_CAT_USUARIOSPERFILES (USUARIOP_USUARIOID, USUARIOP_PERFILID) VALUES (?, ?)";
            $db->execute($sql, [$usuario_id, $perfil_id]);
        }
        $db->close();
    }
}
?>