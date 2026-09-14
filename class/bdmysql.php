<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase MYSQL
*********************************************************************************
*/
?>
<?php

Class DB {

    var $conn;

	function __construct(){
		include("../config/mysql.php");
		$this->conn = mysqli_connect($dbserver, $dbuser, $passwd);
		mysqli_query($this->conn,'SET NAMES utf8');
		//mysqli_query($this->conn,'SET CHARACTER_utf8');
		mysqli_select_db($this->conn,$dbname);
	}

	function &Ejecuta($query){

		$result = $this->conn->query($query);
		$error = $this->conn->error;

		if($error!="")
			$err = $error;
		$rr = "";

		$nrow = $result->num_rows;

		if($nrow > 0){
			$rr = array();
			for ($i=0;$row=$result->fetch_array(MYSQLI_ASSOC);$i++){
				foreach($row as $key => $value)
					$rr[$i][$key]=$value;
			}
		}else {
			$rr =0;
		}
		if($error!=""){
			$rr = $error;
		}
		return($rr);

	}

	function Insert($query){
		$result = $this->conn->query($query);
		echo $this->conn->error;
	}

    function getlastid(){
        return mysqli_insert_id($this->conn);
    }
	
	function &EjecutaJSON($query){
			$result = mysqli_query($this->conn, $query);
			$error = mysqli_error($this->conn);
			if($error!="")
				$err = $error;
			$rr = "";
			if($result instanceof mysqli_result){
				$nrow = mysqli_num_rows($result);
				if($nrow>0){
					$rr = array();
					while ($row = mysqli_fetch_assoc($result)){
  						$data[] = $row;
					}
					$rr = json_encode($data);
				}
				else {
					$rr =0;
				}
			}
			else {
				$rr =0;
			}
			return($rr);
	}

	function close(){
		mysqli_close($this->conn);
	}

	function queryWithPagination($query, $params = [], $limit = 10, $offset = 0) {
		// Agregar paginación al final de la consulta
		$paginatedQuery = $query . " LIMIT ?, ?";
		
		// Preparar declaración
		$stmt = $this->conn->prepare($paginatedQuery);
		
		// Adjuntar parámetros si existen
		if (!empty($params)) {
			$types = str_repeat('s', count($params)) . "ii"; // Agregar tipos para limit y offset
			$params[] = $offset;
			$params[] = $limit;
			$stmt->bind_param($types, ...$params);
		} else {
			$stmt->bind_param("ii", $offset, $limit);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		
		$stmt->close();
		return $data;
	}
	
	function countRows($query, $params = []) {
		// Elimina cualquier "LIMIT" para contar todas las filas
		$countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as subquery";
		
		$stmt = $this->conn->prepare($countQuery);
		
		if (!empty($params)) {
			$types = str_repeat('s', count($params));
			$stmt->bind_param($types, ...$params);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		
		$stmt->close();
		return $row['total'];
	}
}

Class DBmysqli {
	
	var $conn;

	function __construct(){
		include("../config/mysql.php");
		$this->conn = mysqli_connect($dbserver, $dbuser, $passwd);
		mysqli_query($this->conn,'SET NAMES utf8');
		//mysqli_query($this->conn,'SET CHARACTER_utf8');
		mysqli_select_db($this->conn,$dbname);
	}

	function &Ejecutastore($query){
		if ( !($result = mysqli_query($this->conn, $query)) )
			echo mysqli_error($this->conn);
  		if ($result === true)
     		return true;
		for ($i=0;$row = mysqli_fetch_assoc($result);$i++){
			foreach($row as $key => $value){
				$rr[$i][$key]=$value;

			}
		}
  		// Hack for procedures returning second dummy result set
  		while(mysqli_more_results($this->conn)) {
    		mysqli_next_result($this->conn);
    		// echo "* DUMMY RS \n";
  		}
		return($rr);
	}

	function close(){
		mysqli_close($this->conn);
	}
}

?>
