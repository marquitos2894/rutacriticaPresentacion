<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Investigacion de operaciones</title>



  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />

  <link rel="stylesheet" type="text/css" href="sistema/css/jquery-ui-1.10.4.custom.css"/>
  <link rel="stylesheet" type="text/css" href="sistema/css/estilos_electronix.css"/>
  <link rel="stylesheet" type="text/css" href="sistema/css/estilos.css"/>




    <script>
        function removeJquery() {
            $('script').each(function () {

                if (this.src === '../assets/jquery/1.11.3/js/jquery.min.js') {

                    this.parentNode.removeChild(this);
                }
            });
        }

        removeJquery();


    </script>

<?php include 'importar_excel.php';?>

</head>
<body>

    <?php
    procesar();
    ?>
 
 
</body>
</html>


