<?php
session_start();
if (!isset($_SESSION['ampar']['idsesion'])){
	header ("location:../gui/login.php");
}else{
	$usersesion =  $_SESSION['ampar']['usuario'];
    $GLOBALS['isAdmin'] = false; // Initialized here, will be calculated later in includes.php
}
?>