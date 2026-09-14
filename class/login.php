<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase de Login
*********************************************************************************
*/
?>
<?php
class login
{

    function autenticar($usuario, $pwd)
    {
        $db = new FirebirdConnection();
        $sql = "
            select * from AMPAR_CAT_USUARIOS
            where
            USUARIO_CORREO = '" . $usuario . "'
            and USUARIO_PWD = '" . md5($pwd) . "'
        ";
        $resUsuario = $db->query($sql);
        if (!empty($resUsuario)) {
            $usuarioData = $resUsuario[0];
            //Revisar si esta activo
            if ($resUsuario[0]['USUARIO_ACTIVO'] == 1) {
                $usuarioID = $usuarioData['USUARIO_ID'];

                // 🔹 Sucursales asignadas
                $sqlSucursales = "
                    SELECT ampar_cat_sucursales.*
                    FROM AMPAR_CAT_USUARIOSSUCURSALES 
                    LEFT JOIN ampar_cat_sucursales ON SUCURSAL_ID = USUARIOSSUCURSALES_SUCURSALID
                    WHERE USUARIOSSUCURSALES_USUARIOID = " . $usuarioID . "
                ";
                $sucursales = $db->query($sqlSucursales);

                // 🔹 Perfiles asignados
                $sqlPerfiles = "
                    SELECT AMPAR_CAT_PERFILES.*
                    FROM AMPAR_CAT_USUARIOSPERFILES 
                    LEFT JOIN AMPAR_CAT_PERFILES ON PERFIL_ID = USUARIOP_PERFILID
                    WHERE USUARIOP_USUARIOID = " . $usuarioID . "
                ";
                $perfiles = $db->query($sqlPerfiles);

                // 🔹 Menús permitidos por perfil
                $sqlMenu = "
                    SELECT DISTINCT AMPAR_CONF_MENU.*
                    FROM AMPAR_CAT_USUARIOSPERFILES
                    LEFT JOIN AMPAR_CAT_PERFILES ON PERFIL_ID = USUARIOP_PERFILID
                    LEFT JOIN AMPAR_CAT_PERFILMENU ON PERFILMENU_PERFILID = PERFIL_ID
                    LEFT JOIN AMPAR_CONF_MENU ON MENU_ID = PERFILMENU_MENUID
                    WHERE USUARIOP_USUARIOID = " . $usuarioID . "
                ";
                $menu = $db->query($sqlMenu);

                $_SESSION['ampar'] = [
                    'usuario' => $usuarioData,
                    'sucursales' => $sucursales,
                    'perfiles' => $perfiles,
                    'menu' => $menu,
                    'idsesion' => 'Ampar2025!'
                ];

                echo 'success';
                //bitacora::guardar('Login corecto. <b>Usuario:</b> '.$usuario,$res[0]['usuarios_id'],$res[0]['usuarios_nombre']);
            } else {
                //bitacora::guardar('Intento de Login con <b>usuario:</b> '.$usuario.'. Usuario inactivo',$res[0]['usuarios_id'],$res[0]['usuarios_nombre']);
                echo "Usuario inactivo.";
            }
        } else {
            //Validar si existe el usuario y la contraseña es incorrecta
            $sql2 = "
                select * from AMPAR_CAT_USUARIOS
                where
                USUARIO_CORREO = '" . $usuario . "'
            ";
            $res2 = $db->query($sql2);
            if (empty($res2)) {
                //bitacora::guardar('Intento de Login con usuario no registrado. Usuario: '.$usuario,null,'');
                echo "Usuario no registrado.";
            } else {
                //bitacora::guardar('Intento de Login con usuario: '.$usuario.'. La contraseña es incorrecta.',$res2[0]['usuarios_id'],$res2[0]['usuarios_nombre']);
                echo "Contraseña incorrecta.";
            }
        }
        $db->close();
        return $resUsuario;
    }

    function recuperarpwd($usuario)
    {
        $db = new FirebirdConnection();
        //Validar que exista el usuario
        $query2 = "
            select * from AMPAR_CAT_USUARIOS
            where
            USUARIO_CORREO = '" . $usuario . "'
        ";
        $res2 = $db->query($query2);
        if (empty($res2)) {
            //bitacora::guardar('Intento de recuperación de contraseña incorrecto. No existe usuario: '.$usuario,null,$usuario,'Login');
            echo "Usuario no registrado.";
        } else {
            //generar codigo y enviar por correo
            $token = $this->generarTokenAlfanumerico();
            //Guardar en Base de datos
            $query3 = "
                insert into AMPAR_USUARIOTOKEN
                    (  
                        TOKEN_USUARIOCORREO,
                        TOKEN_CODIGO,
                        TOKEN_FECHACREACION
                    )
                values
                    (
                        '" . $usuario . "',
                        '" . $token . "',
                        CURRENT_TIMESTAMP
                    )
            ";
            $db->execute($query3);
            $db->Close();
            //Enviar Correo
            $correo = new mail();
            $mensaje = '<h2>Código de Recuperación</h2>';
            $mensaje .= '<br><b>El código para recuperar tu contraseña es:</b><br><br><span style="font-size:50px; color:gray"> ' . $token . '</span>';
            $correo->enviar($usuario, $res2[0]['USUARIO_NOMBRE'], 'Código de Recuperación', $mensaje);
            //bitacora::guardar('Se envió código para recuperar contraseña a '.$usuario,$res2[0]['consultores_id'],$res2[0]['consultores_nombre'],'Consultor');
        }
    }

    function generarTokenAlfanumerico($longitud = 6)
    {
        // Caracteres permitidos en el token
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';

        // Generar el token seleccionando caracteres aleatorios
        for ($i = 0; $i < $longitud; $i++) {
            $token .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return $token;
    }

    function validaexistecorreousuario($correo)
    {
        $db = new FirebirdConnection();
        //Validar que exista el usuario
        $query2 = "
            select count(*) CONTADOR from AMPAR_CAT_USUARIOS
            where
            USUARIO_CORREO = '" . $correo . "'
        ";
        $res2 = $db->query($query2);
        $db->Close();
        echo $res2[0]['CONTADOR'];
    }

    function agregarusuario($sucursales, $tipo, $subgrupos, $usuario, $nombre, $alias, $pwd, $files, $telefono = '')
    {
        $db = new FirebirdConnection();
        //Validar si existe el usuario
        $query2 = "
            select * from AMPAR_CAT_USUARIOS
            where
            USUARIO_CORREO = '" . $usuario . "'
        ";
        $res2 = $db->query($query2);
        if (empty($res2)) {
            $query = "
                insert into AMPAR_CAT_USUARIOS
                (
                    USUARIO_CORREO,
                    USUARIO_NOMBRE,
                    USUARIO_ALIAS,
                    USUARIO_PWD,
                    USUARIO_TELEFONO,
                    USUARIO_ACTIVO
                )
                values
                (
                    '" . $usuario . "',
                    '" . $nombre . "',
                    '" . $alias . "',
                    '" . md5($pwd) . "',
                    '" . $telefono . "',
                    1
                )
            ";
            $lastid = $db->executeconreturning($query, 'USUARIO_ID');
            //Guardar Imagen
            $nombreimagenproyecto = '../img/nodisponible.jpg';
            if (!empty($files)) {
                $directorioDestino = '../images/perfil/';
                if (!is_dir($directorioDestino)) {
                    mkdir($directorioDestino, 0777, true);
                }
                $rutaDestino = $directorioDestino . $lastid . '.' . pathinfo($files['imagen']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($files['imagen']['tmp_name'], $rutaDestino);
                $nombreimagenproyecto = $rutaDestino;
            }
            //Guardar almacenes (vinculación directa usuario-almacén)
            if (!empty($sucursales) && is_array($sucursales)) {
                foreach ($sucursales as $almacenId) {
                    $query2 = "
                        insert into AMPAR_CAT_USUARIOSALMACENES
                        (
                            USUARIOSALMACENES_USUARIOID,
                            USUARIOSALMACENES_ALMACENID
                        )
                        values
                        (
                            " . $lastid . ",
                            " . $almacenId . "
                        )
                    ";
                    $db->execute($query2);
                }
            }
            //Guardar tipos y perfiles espejo
            if (!empty($tipo) && is_array($tipo)) {
                foreach ($tipo as $ti) {
                    $query3 = "
                        insert into AMPAR_CAT_USUARIOSTIPOPERMISOS
                        (
                            USUARIOSTIPOPERMISOS_USUARIOID,
                            USUARIOSTIPOPERMISOS_TIPOID
                        )
                        values
                        (
                            " . $lastid . ",
                            " . $ti . "
                        )
                    ";
                    $db->execute($query3);

                    // 🔹 NUEVO: Insertar automáticamente el mismo ID como Perfil
                    $queryPerfil = "
                        insert into AMPAR_CAT_USUARIOSPERFILES
                        (
                            USUARIOP_USUARIOID,
                            USUARIOP_PERFILID
                        )
                        values
                        (
                            " . $lastid . ",
                            " . $ti . "
                        )
                    ";
                    $db->execute($queryPerfil);
                }
            }
            //Guardar subgrupos
            if (!empty($subgrupos) && is_array($subgrupos)) {
                foreach ($subgrupos as $sg) {
                    $query4 = "
                        insert into AMPAR_CAT_USUARIOSSUBGRUPOS
                        (
                            USUARIOSSUBGRUPOS_USUARIOID,
                            USUARIOSSUBGRUPOS_SUBGRUPOID
                        )
                        values
                        (
                            " . $lastid . ",
                            " . $sg . "
                        )
                    ";
                    $db->execute($query4);
                }
            }
            //UPDATE
            $db->execute("update AMPAR_CAT_USUARIOS SET USUARIO_RUTAIMAGEN='" . $nombreimagenproyecto . "' where USUARIO_ID = " . $lastid);
            $db->Close();
        } else {
            echo "Ya existe un consultor registrado con este correo";
        }
    }

    function editarusuario($usuario_id, $sucursales, $tipo, $subgrupos, $usuario, $nombre, $alias, $pwd, $files, $telefono = '')
    {
        $db = new FirebirdConnection();

        // Construir la parte del UPDATE que siempre se actualiza
        $queryUpdate = "
            UPDATE AMPAR_CAT_USUARIOS SET
                USUARIO_CORREO    = '" . $usuario . "',
                USUARIO_NOMBRE    = '" . $nombre . "',
                USUARIO_ALIAS     = '" . $alias . "',
                USUARIO_TELEFONO  = '" . $telefono . "'
        ";

        // Si se envió contraseña y no está vacía, actualizarla
        if (!empty($pwd)) {
            $queryUpdate .= ", USUARIO_PWD = '" . md5($pwd) . "'";
        }

        // Procesar imagen si existe archivo subido
        if (!empty($files) && isset($files['imagen']) && $files['imagen']['tmp_name'] != '') {
            $directorioDestino = '../images/perfil/';
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0777, true);
            }
            $rutaDestino = $directorioDestino . $usuario_id . '.' . pathinfo($files['imagen']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($files['imagen']['tmp_name'], $rutaDestino);
            $queryUpdate .= ", USUARIO_RUTAIMAGEN = '" . $rutaDestino . "'";
        }

        // Completar el query con WHERE
        $queryUpdate .= " WHERE USUARIO_ID = " . $usuario_id;

        // Ejecutar UPDATE
        $db->execute($queryUpdate);

        // Eliminar permisos anteriores
        $db->execute("DELETE FROM AMPAR_CAT_USUARIOSTIPOPERMISOS WHERE USUARIOSTIPOPERMISOS_USUARIOID = " . $usuario_id);
        // 🔹 NUEVO: Eliminar también los perfiles anteriores
        $db->execute("DELETE FROM AMPAR_CAT_USUARIOSPERFILES WHERE USUARIOP_USUARIOID = " . $usuario_id);

        $db->execute("DELETE FROM AMPAR_CAT_USUARIOSALMACENES WHERE USUARIOSALMACENES_USUARIOID = " . $usuario_id);
        $db->execute("DELETE FROM AMPAR_CAT_USUARIOSSUBGRUPOS WHERE USUARIOSSUBGRUPOS_USUARIOID = " . $usuario_id);

        // Insertar los permisos nuevos - Almacenes
        if (!empty($sucursales) && is_array($sucursales)) {
            foreach ($sucursales as $almacenId) {
                $queryAlmacenes = "
                    INSERT INTO AMPAR_CAT_USUARIOSALMACENES
                    (
                        USUARIOSALMACENES_USUARIOID,
                        USUARIOSALMACENES_ALMACENID
                    )
                    VALUES
                    (
                        " . $usuario_id . ",
                        " . $almacenId . "
                    )
                ";
                $db->execute($queryAlmacenes);
            }
        }

        // Insertar tipos y perfiles espejo
        if (!empty($tipo) && is_array($tipo)) {
            foreach ($tipo as $ti) {
                $queryTipo = "
                    INSERT INTO AMPAR_CAT_USUARIOSTIPOPERMISOS
                    (
                        USUARIOSTIPOPERMISOS_USUARIOID,
                        USUARIOSTIPOPERMISOS_TIPOID
                    )
                    VALUES
                    (
                        " . $usuario_id . ",
                        " . $ti . "
                    )
                ";
                $db->execute($queryTipo);

                // 🔹 NUEVO: Insertar también el perfil correspondiente
                $queryPerfil = "
                    INSERT INTO AMPAR_CAT_USUARIOSPERFILES
                    (
                        USUARIOP_USUARIOID,
                        USUARIOP_PERFILID
                    )
                    VALUES
                    (
                        " . $usuario_id . ",
                        " . $ti . "
                    )
                ";
                $db->execute($queryPerfil);
            }
        }

        // Insertar subgrupos
        if (!empty($subgrupos) && is_array($subgrupos)) {
            foreach ($subgrupos as $sub) {
                $querySubgrupo = "
                    INSERT INTO AMPAR_CAT_USUARIOSSUBGRUPOS
                    (
                        USUARIOSSUBGRUPOS_USUARIOID,
                        USUARIOSSUBGRUPOS_SUBGRUPOID
                    )
                    VALUES
                    (
                        " . $usuario_id . ",
                        " . $sub . "
                    )
                ";
                $db->execute($querySubgrupo);
            }
        }

        $db->Close();
        echo ""; // éxito sin errores
    }


    function editarconsultor($consultorid, $pwd, $nombre, $genero, $fechanacimiento/*,$empresa,$rfc,$direccion,$giro*/, $files)
    {
        //Consulta información de registro
        $infoori = $this->getconsultorbyid($consultorid);
        $db = new DB();
        $query = "
            update _cat_consultores
            set
                consultores_nombre = '" . $nombre . "',
                consultores_genero = " . $genero . ",
                consultores_fechanacimiento = '" . $fechanacimiento . "'
            where consultores_id = " . $consultorid . "
        ";
        $db->Insert($query);
        if ($pwd <> "") {
            $query2 = "
                update _cat_consultores
                set
                    consultores_pwd = md5('" . $pwd . "')
                where consultores_id = " . $consultorid . "
            ";
            $db->Insert($query2);
        }
        //Guardar Imagen
        if (!empty($files)) {
            $directorioDestino = '../uploads/profile/';
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0777, true);
            }
            $rutaDestino = $directorioDestino . $consultorid . '.' . pathinfo($files['imagen']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($files['imagen']['tmp_name'], $rutaDestino);
            $db->Insert("update _cat_consultores set consultores_rutaimagen='" . $rutaDestino . "' where consultores_id = " . $consultorid);
        }
        $db->Close();
        //Consulta información de registro
        $infocambio = $this->getconsultorbyid($consultorid);
        $printinfo  = bitacora::printarraycomparacion($infoori[0], $infocambio[0]);
        $usersesion =  unserialize($_SESSION['estratego']['usuario']);
        bitacora::guardar('Se modificó registro consultor. ' . $printinfo, $usersesion[0]['consultores_id'], $usersesion[0]['consultores_nombre'], 'Consultor');
    }

    function getinfoconsultorbyusuario($correo)
    {
        $db = new DB();
        $query = "
            select * from _cat_consultores
            where
            consultores_usuario = '" . $correo . "'
        ";
        $res = $db->Ejecuta($query);
        $db->Close();
        return $res;
    }

    function validarcorreocodigo($correo, $codigo)
    {
        $db = new DB();
        //Validar que exista el usuario
        $query2 = "
            SELECT count(*) contador 
            FROM _token 
            where token_consultoresusuario = '" . $correo . "' 
            and token_codigo = '" . $codigo . "'
            and '" . date("Y-m-d H:i:s") . "' < DATE_ADD(token_fechacreacion, INTERVAL 10 MINUTE);
        ";
        $res2 = $db->Ejecuta($query2);
        $db->Close();
        if ($res2[0]['contador'] == 0) {
            echo "No se encontró código";
        } else {
            //Validar por base de datos al usuario
            $db = new DB();
            //Validar que exista el usuario
            $query2 = "
                update _cat_consultores
                set 
                consultores_validado = 1
                where
                consultores_usuario = '" . $correo . "'
            ";
            $db->Insert($query2);
            $db->Close();
            echo "Usuario validado";
            $infoc = $this->getinfoconsultorbyusuario($correo);
            bitacora::guardar('Se validó cuenta ' . $correo, $infoc[0]['consultores_id'], $infoc[0]['consultores_nombre'], 'Consultor');
        }
    }

    function enviarcodigo($correo)
    {
        $db = new DB();
        $token = $this->generarTokenAlfanumerico();
        //Guardar en Base de datos
        $query3 = "
                insert into _token
                    (  
                        token_consultoresusuario,
                        token_codigo,
                        token_fechacreacion
                    )
                values
                    (
                        '" . $correo . "',
                        '" . $token . "',
                        '" . date("Y-m-d H:i:s") . "'
                    )
            ";
        $db->Insert($query3);
        $db->Close();
        //Enviar correo para validar cuenta
        $mail = new mail();
        $mensaje = '<h2>Código de Validación</h2>';
        $mensaje .= '<br><b>El código para validar tu cuenta es:</b><br><br><span style="font-size:50px; color:gray"> ' . $token . '</span>';
        $mail->enviar($correo, $correo, 'Código de Validación', $mensaje);
        $infoc = $this->getinfoconsultorbyusuario($correo);
        bitacora::guardar('Se envió código para validar cuenta a ' . $correo, $infoc[0]['consultores_id'], $infoc[0]['consultores_nombre'], 'Consultor');
    }

    function validarchangepwd($correo, $codigo)
    {
        $db = new FirebirdConnection();
        //Validar que exista el usuario
        $query2 = "
            SELECT count(*) CONTADOR 
            FROM AMPAR_USUARIOTOKEN 
            where TOKEN_USUARIOCORREO = '" . $correo . "' 
            and TOKEN_CODIGO = '" . $codigo . "'
            and CURRENT_TIMESTAMP < TOKEN_FECHACREACION + (1000.0 / 1440)
        ";
        //echo $query2;
        $res2 = $db->query($query2);
        $db->Close();
        return $res2[0]['CONTADOR'];
    }

    function updatepwd($correo, $pwd)
    {
        $db = new FirebirdConnection();
        $query = "
            update
            AMPAR_CAT_USUARIOS
            SET USUARIO_PWD = '" . md5($pwd) . "'
            where USUARIO_CORREO = '" . $correo . "'
            ";
        $db->execute($query);
        $db->Close();
        //$infoc = $this->getinfoconsultorbyusuario($correo);
        //bitacora::guardar('Actualización de contraseña consultor: '.$correo,$infoc[0]['consultores_id'],$infoc[0]['consultores_nombre'],'Consultor');
    }

    function updateactivo($id, $bandera)
    {
        $db = new FirebirdConnection();
        $query = "
            update
            AMPAR_CAT_USUARIOS
            SET USUARIO_ACTIVO = " . $bandera . "
            where USUARIO_ID = " . $id . "
            ";
        $db->execute($query);
        $db->Close();
        //Consulta información de registro
        //$info = $this->getconsultorbyid($consultorid);
        //$usersesion =  unserialize($_SESSION['estratego']['usuario']);
        //bitacora::guardar('Se '.(($bandera==1)?'activó':'desactivó')." consultor ".$info[0]['consultores_nombre'].".",$usersesion[0]['consultores_id'],$usersesion[0]['consultores_nombre'],'Consultor');
    }

    function getusuarios()
    {
        $db = new FirebirdConnection();
        $query = "
            SELECT U.*, 
            (SELECT LIST(TIPOS.USUARIOTIPO_NOMBRE, ', ') 
             FROM AMPAR_CAT_USUARIOSTIPOPERMISOS UTP 
             LEFT JOIN AMPAR_CONF_USUARIOSTIPO TIPOS ON TIPOS.USUARIOTIPO_ID = UTP.USUARIOSTIPOPERMISOS_TIPOID 
             WHERE UTP.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID) AS USUARIOTIPO_NOMBRE
            FROM AMPAR_CAT_USUARIOS U
        ";
        $res = $db->query($query);
        $db->Close();
        return $res;
    }

    function getusuariobyid($usuarioID)
    {
        $db = new FirebirdConnection();
        $query = "
            SELECT * FROM AMPAR_CAT_USUARIOS
            WHERE USUARIO_ID = " . $usuarioID . "
        ";
        $resUsuario = $db->query($query);
        $usuarioData = $resUsuario[0];

        // 🔹 Tipos asignadas
        $sqlTipos = "
            SELECT TIPOS.*
            FROM AMPAR_CAT_USUARIOSTIPOPERMISOS 
            LEFT JOIN AMPAR_CONF_USUARIOSTIPO TIPOS ON USUARIOTIPO_ID = USUARIOSTIPOPERMISOS_TIPOID
            WHERE USUARIOSTIPOPERMISOS_USUARIOID = " . $usuarioID . "
        ";
        $tipos = $db->query($sqlTipos);

        // 🔹 Almacenes asignados
        $sqlAlmacenes = "
            SELECT ALMACEN.ALMACEN_ID, ALMACEN_NOMBRE
            FROM AMPAR_CAT_USUARIOSALMACENES
            LEFT JOIN AMPAR_HIS_ALMACEN ALMACEN ON ALMACEN_ID = USUARIOSALMACENES_ALMACENID
            WHERE USUARIOSALMACENES_USUARIOID = " . $usuarioID . "
        ";
        $almacenes = $db->query($sqlAlmacenes);

        // 🔹 Subgrupo asignadas
        $sqlSubgrupos = "
            SELECT AMPAR_CAT_TIPOEVENTOSUBGRUPO.*
            FROM AMPAR_CAT_USUARIOSSUBGRUPOS 
            LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO ON TIPOEVENTOSUBGRUPO_ID = USUARIOSSUBGRUPOS_SUBGRUPOID
            WHERE USUARIOSSUBGRUPOS_USUARIOID = " . $usuarioID . "
        ";
        $subgrupo = $db->query($sqlSubgrupos);

        // 🔹 Perfiles asignados
        $sqlPerfiles = "
            SELECT AMPAR_CAT_PERFILES.*
            FROM AMPAR_CAT_USUARIOSPERFILES 
            LEFT JOIN AMPAR_CAT_PERFILES ON PERFIL_ID = USUARIOP_PERFILID
            WHERE USUARIOP_USUARIOID = " . $usuarioID . "
        ";
        $perfiles = $db->query($sqlPerfiles);

        // 🔹 Menús permitidos por perfil
        $sqlMenu = "
            SELECT DISTINCT AMPAR_CONF_MENU.*
            FROM AMPAR_CAT_USUARIOSPERFILES
            LEFT JOIN AMPAR_CAT_PERFILES ON PERFIL_ID = USUARIOP_PERFILID
            LEFT JOIN AMPAR_CAT_PERFILMENU ON PERFILMENU_PERFILID = PERFIL_ID
            LEFT JOIN AMPAR_CONF_MENU ON MENU_ID = PERFILMENU_MENUID
            WHERE USUARIOP_USUARIOID = " . $usuarioID . "
        ";
        $menu = $db->query($sqlMenu);

        $res = [
            'usuario' => $usuarioData,
            'tipos' => $tipos,
            'almacenes' => $almacenes,
            'subgrupo' => $subgrupo,
            'perfiles' => $perfiles,
            'menu' => $menu,
            'idsesion' => 'Ampar2025!'
        ];

        $db->Close();
        return $res;
    }

    function getcatalogotipousuario()
    {
        $db = new FirebirdConnection();
        $query = "
            SELECT USUARIOTIPO_ID ID , USUARIOTIPO_NOMBRE NOMBRE FROM AMPAR_CONF_USUARIOSTIPO
            ORDER BY USUARIOTIPO_NOMBRE
        ";
        $res = $db->query($query);
        $db->Close();
        return $res;
    }

    function getcatalogosubgruposusuario()
    {
        $db = new FirebirdConnection();
        $query = "
            SELECT 
                TIPOEVENTOSUBGRUPO_ID AS ID,
                TIPOEVENTOSUBGRUPO_NOMBRE || ' (' || TIPOEVENTOGRUPO_NOMBRE || ')' AS NOMBRE
            FROM AMPAR_CAT_TIPOEVENTOSUBGRUPO
            LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO ON TIPOEVENTOGRUPO_ID = TIPOEVENTOSUBGRUPO_GRUPOID
            ORDER BY TIPOEVENTOSUBGRUPO_NOMBRE
        ";
        $res = $db->query($query);
        $db->Close();
        return $res;
    }

    function getcatalogousuarios($term)
    {
        $db = new FirebirdConnection();
        $query = "
            SELECT USUARIO_ID ID , USUARIO_NOMBRE NOMBRE, USUARIO_NOMBRE LABEL 
            FROM AMPAR_CAT_USUARIOS
            WHERE UPPER(USUARIO_NOMBRE) LIKE '%" . $term . "%'
            ORDER BY USUARIO_NOMBRE
        ";
        $res = $db->query($query);
        $db->Close();
        return $res;
    }

    function actualizarusuariosucursales($usuarioid, $sucursalid, $permiso)
    {
        $db = new FirebirdConnection();
        if ($permiso == true) {
            // Insertar si no existe
            $existe = $db->query("SELECT 1 FROM AMPAR_CAT_USUARIOSSUCURSALES 
                                  WHERE USUARIOSSUCURSALES_USUARIOID = " . $usuarioid . " AND USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "");
            if (empty($existe)) {
                $db->execute("INSERT INTO AMPAR_CAT_USUARIOSSUCURSALES (USUARIOSSUCURSALES_USUARIOID, USUARIOSSUCURSALES_SUCURSALID)
                            VALUES (" . $usuarioid . ", " . $sucursalid . ")");
            }
        } else {
            // Eliminar permiso
            $db->execute("DELETE FROM AMPAR_CAT_USUARIOSSUCURSALES 
                        WHERE USUARIOSSUCURSALES_USUARIOID = " . $usuarioid . " AND USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "");
        }
        $db->Close();
    }
}
?>