<?php

    include '../../../Config/Conexion/config.php';
    include '../../../Generic/Database/DataBase.php';
    include '../../../Clases/cls_Parametros.php';


    if ($_GET['id'] == "") {
        echo '<script >
        self.location = "ProductosServiciosGrupos.php?me=1";
	</script>';
    }

    $Ter = new cls_Parametros();

    $ID = $_GET['id'];

    if ($Ter->EliminarProducto($ID) > 0) {

        echo '<script >
                      window.location.href = "ProductosServiciosGrupos.php?me=1";
                     </script>';
//             
    } else {
        echo '<script >
	alert("Error al eliminar" );
	</script>';
        header('location:ProductosServiciosGrupos.php?me=1');
    }





?>
