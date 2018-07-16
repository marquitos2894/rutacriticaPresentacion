<?php

require_once 'reader/Classes/PHPExcel/IOFactory.php';

class ImportarExcel {

    //Funciones extras
    function get_cell($cell, $objPHPExcel) {
        //seleccionar una celda
        $objCell = ($objPHPExcel->getActiveSheet()->getCell($cell));
        //obtener valor de celda
        return $objCell->getvalue();
    }

    function pp(&$var) {
        //obtiene la siguiente letra
        $var = chr(ord($var) + 1);
        return true;
    }

    function importar_excel_array($archivo) {
        if (isset($archivo)) {

            $name = $archivo['name'];
            $tname = $archivo['tmp_name'];
            $type = $archivo['type'];

            if ($type == 'application/vnd.ms-excel') {
                // Extension excel 97
                $ext = 'xls';
            } else if ($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                // Extension excel 2007 y 2010
                $ext = 'xlsx';
            } else {
                // Extension no valida
                echo "Error en la carga de archivo. Asegúrece de seleccionar un archivo Excel";
                exit();
            }

            $xlsx = 'Excel2007';
            $xls = 'Excel5';

            //creando el lector
            $objReader = PHPExcel_IOFactory::createReader($$ext);

            //cargamos el archivo
            $objPHPExcel = $objReader->load($tname);

            $dim = $objPHPExcel->getActiveSheet()->calculateWorksheetDimension();

            // list coloca en array $start y $end
            list($start, $end) = explode(':', $dim);

            if (!preg_match('#([A-Z]+)([0-9]+)#', $start, $rslt1)) {
                return false;
            }
            list($start, $start_h, $start_v) = $rslt1;
            if (!preg_match('#([A-Z]+)([0-9]+)#', $end, $rslt2)) {
                return false;
            }
            list($end, $end_h, $end_v) = $rslt2;


            $datos = array();
            $datos[0] = array('A' => "", 'B' => "", 'C' => "", 'D' => "", 'E' => "");

            //empieza  lectura vertical
            for ($v = $start_v; $v <= $end_v; $v++) {

                //empieza lectura horizontal
                $columna = array();
                for ($h = $start_h; ord($h) <= ord($end_h); $this->pp($h)) {
                    $cellValue = $this->get_cell($h . $v, $objPHPExcel);

                    if ($cellValue !== null) {
                        $columna[$h] = $cellValue;
                    } else
                        $columna[$h] = "";
                }

                $datos[] = $columna;
            }
            unset($datos[0]);
            return $datos;
        }
    }

//fin de funcion

    function actividades($datos) {

    }

}




//funciones
function procesar(){
    $cont = 1;
    global $num_filas,$num_columnas;
    //aqui tener obtener la informacion de la tabla html
    //$miarray = $obj->importar_excel_array($archivo);

    $miarray = json_decode($_POST["arreglo"], true);
   
   

    $miarray = $miarray['Actividades'];
    unset($miarray[0]);
    //array_push($miarray,["A"=>" ","B"=>"","C"=>"","D"=>"","E"=>"","F"=>"","G"=>"","H"=>""]);
    $matriz = array();

    //creacion de la matriz
    foreach ($miarray as $fila => $valor) {
        if ($cont == 1) {
            $cont++;
            continue;
        }
        $key = strtoupper(trim($valor['A']));
        if ($key != "") {
            $matriz[$key]['predecesor'] = explode(",", trim($valor['B']));
            $matriz[$key]['duracion'] = $valor['D'];
            $matriz[$key]['nivel_columna'] = '0';
            $matriz[$key]['tipo'] = 'normal';
            $matriz[$key]['sucesor'] = array();

            $matriz[$key]['duracion_choque'] = $valor['E'];
            $matriz[$key]['costo_normal'] = $valor['F'];
            $matriz[$key]['costo_choque'] = $valor['G'];
            if ($valor['D'] - $valor['E'] != 0)
                $matriz[$key]['choque_periodo'] = round(($valor['G'] - $valor['F']) / ($valor['D'] - $valor['E']), 2);
            else
                $matriz[$key]['choque_periodo'] = 0;
        }
    }

    //----------------------
    foreach ($matriz as $actividad => $value) {
        foreach ($value['predecesor'] as $key => $value2) {
            $matriz[$actividad]['predecesor'][$key] = trim($value2);
        }
    }

    //----------------------
    $existe_ficticio = true;
    while ($existe_ficticio) {
        $nueva_letra_ficticia = "";
        foreach ($matriz as $actividad => $valor) {
            $existe_ficticio = false;
            if (count($valor['predecesor']) > 1) {

                foreach ($matriz as $actividad2 => $valor2) {
                    if ($valor['predecesor'] == $valor2['predecesor'] && $actividad != $actividad2) {
                        $existe_ficticio = true;
                        $nueva_letra_ficticia = chr(65 + count($matriz));
                        $matriz[$actividad2]['predecesor'] = array($nueva_letra_ficticia);
                    }
                }
            }

            if ($existe_ficticio == true) {
                $matriz[$nueva_letra_ficticia]['predecesor'] = $valor['predecesor'];
                $matriz[$nueva_letra_ficticia]['duracion'] = '0';
                $matriz[$nueva_letra_ficticia]['nivel_columna'] = '0';
                $matriz[$nueva_letra_ficticia]['tipo'] = 'ficticio';
                $matriz[$nueva_letra_ficticia]['sucesor'] = array();
                $matriz[$actividad]['predecesor'] = array($nueva_letra_ficticia);

                $matriz[$nueva_letra_ficticia]['duracion_choque'] = 0;
                $matriz[$nueva_letra_ficticia]['costo_normal'] = 0;
                $matriz[$nueva_letra_ficticia]['costo_choque'] = 0;
                $matriz[$nueva_letra_ficticia]['choque_periodo'] = 0;
                break; //termina el foreach
            }
        }
    }

    //----------------------
    foreach ($matriz as $actividad => $valor) {
        foreach ($valor['predecesor'] as $key => $valor2) {
            if ($valor2 != 'ninguno') {
                $matriz[$valor2]['sucesor'][] = $actividad;
            }
        }
    }
    foreach ($matriz as $actividad => $valor) {
        if (count($valor['sucesor']) == 0)
            $matriz[$actividad]['sucesor'][] = 'ninguno';
    }


    $nivel_columna = 1;
    //----------------------
    foreach ($matriz as $actividad => $valor) {
        if ($valor['predecesor'][0] == "ninguno")
            $matriz[$actividad]['nivel_columna'] = $nivel_columna;
    }


    $nivel_columna++;
    while ($nivel_columna <= count($matriz)) {
        foreach ($matriz as $actividad => $valor) {
            if ($valor['nivel_columna'] == '0') {

                $estado = false;
                foreach ($valor['predecesor'] as $key2 => $valor2) {
                    if ($matriz[$valor2]['nivel_columna'] == $nivel_columna - 1) {
                        $estado = true;
                    }
                    if ($matriz[$valor2]['nivel_columna'] == 0 ||
                            $matriz[$valor2]['nivel_columna'] == $nivel_columna) {

                        $estado = false;
                        break; //sale del foreach
                    }
                }
                if ($estado) {
                    $matriz[$actividad]['nivel_columna'] = $nivel_columna;
                }
            }
        }
        $nivel_columna++;
    }



    //----------------------
    $num_columnas = 0;
    foreach ($matriz as $actividad => $valor) {
        if ($valor['nivel_columna'] > $num_columnas)
            $num_columnas = $valor['nivel_columna'];
    }
    //---
    //----------------------
    $matriz2 = array();
    $matriz2 = $matriz;

    foreach ($matriz2 as $actividad => $valor) {
        $cont_tmp = 0;
        foreach ($valor['predecesor'] as $key => $valor2) {

            if ($valor['nivel_columna'] >= 3 && ($valor['nivel_columna'] - $matriz[$valor2]['nivel_columna']) >= 2) {

                $columna = $matriz[$valor2]['nivel_columna'];
                $diferencia = $valor['nivel_columna'] - $matriz[$valor2]['nivel_columna'];
                $predecesor = $valor2;
                for ($i = 1; $i < $diferencia; $i++) {
                    $cont_tmp++;
                    $matriz2['X' . $cont_tmp]['predecesor'] = array($predecesor);
                    $matriz2['X' . $cont_tmp]['sucesor'] = array();
                    $matriz2['X' . $cont_tmp]['nivel_columna'] = $columna + $i;
                    $matriz2['X' . $cont_tmp]['tipo'] = "temporal";
                    $predecesor = 'X' . $cont_tmp;
                }
                $matriz2[$actividad]['predecesor'][$key] = $predecesor;
            }
        }
    }


    //----------------------
    $nivel_fila = 1;
    foreach ($matriz2 as $actividad => $valor) {
        if ($valor['nivel_columna'] == 1) {
            $matriz2[$actividad]['nivel_fila'] = $nivel_fila;
            $nivel_fila++;
        }
    }


    //----------------------
    for ($columna = 2; $columna <= $num_columnas; $columna++) {
        foreach ($matriz2 as $actividad => $valor) {
            if ($valor['nivel_columna'] == $columna) {
                foreach ($valor['predecesor'] as $key => $valor2) {
                    $num_adignados = 0;

                    foreach ($matriz2 as $actividad3 => $valor3) {
                        if ($valor3['nivel_columna'] == $columna &&
                                isset($valor3['nivel_fila'])) {
                            $num_adignados++;
                        }
                    }


                    if ($num_adignados >= 1) {


                        $nivel_fila = obtener_nivel_fila($matriz2, $matriz2[$valor2]['nivel_fila'], $columna);
                        $matriz2[$actividad]['nivel_fila'] = $nivel_fila;
                        break;
                    } else {

                        $matriz2[$actividad]['nivel_fila'] = $matriz2[$valor2]['nivel_fila'];
                        break; //sale del foreach
                    }
                }//fin de foreach de predecesores
            }//fin de if
        }//fin de foreach de la matriz2
    }//fin de for
    //----------------------
    $num_filas = 0;
    foreach ($matriz2 as $actividad => $valor) {
        if ($valor['nivel_fila'] > $num_filas)
            $num_filas = $valor['nivel_fila'];
    }

    //----------------------
    $matriz2['ninguno']['predecesor'] = array();
    $matriz2['ninguno']['duracion'] = 0;
    $matriz2['ninguno']['nivel_columna'] = 0;
    $matriz2['ninguno']['tipo'] = 'fini';
    $matriz2['ninguno']['nivel_fila'] = 1;
    $matriz2['fin']['predecesor'] = array();
    $matriz2['fin']['duracion'] = 0;
    $matriz2['fin']['nivel_columna'] = $num_columnas + 1;
    $matriz2['fin']['tipo'] = 'fini';
    $matriz2['fin']['nivel_fila'] = 1;
    foreach ($matriz as $actividad => $valor) {
        $existe = false;
        foreach ($matriz as $actividad2 => $valor2) {
            foreach ($valor2['predecesor'] as $key => $valor3) {
                if ($actividad == $valor3)
                    $existe = true;
            }
        }
        if (!$existe) {
            $matriz2['fin']['predecesor'][] = $actividad;
        }
    }



    //----------------------
    $columna = 0;
    $fila = 0;
    while ($columna <= $num_columnas + 1) {
        $num_act_columna = 0;

        foreach ($matriz2 as $actividad => $valor) {
            if ($valor['nivel_columna'] == $columna) {
                $num_act_columna++;
            }
        }
        foreach ($matriz2 as $actividad => $valor) {
            if ($valor['nivel_columna'] == $columna) {
                $nueva_posicion = $matriz2[$actividad]['nivel_fila'] + floor(($num_filas - $num_act_columna) / 2);
                //if($nueva_posicion<$num_filas)
                //$matriz2[$actividad]['nivel_fila'] = $nueva_posicion;
            }
        }
        $columna++;
    }




    //---------------------------------------------------------------------------------------

    $iteraciones = array();
    $iteraciones[0]['matriz'] = $matriz;

    $iteraciones[0]['duracion_fin'] = 0;
    $iteraciones[0]['ruta_critica'] = array();
    $iteraciones[0]['letras_ruta_critica'] = array();
    $iteraciones[0]['actividad_reducido'] = '';
    $iteraciones[0]['html_tabla'] = "";
    $iteraciones[0]['html_actividades'] = "";
    $iteraciones[0]['html_enlaces'] = "";
    $iteraciones[0]['anulado'] = array();




    calcular_tiempos($iteraciones[0]['matriz'], $matriz2, $iteraciones[0]['duracion_fin'], $num_columnas);



    obtener_ruta_critica($iteraciones[0]['letras_ruta_critica'], $iteraciones[0]['ruta_critica'], $iteraciones[0]['matriz']);


    crear_html($iteraciones[0]['html_tabla'], $iteraciones[0]['html_actividades'], $iteraciones[0]['html_enlaces'], $matriz2, $iteraciones[0]
    );







    //---------------------------------------------------------------------------------------
    //----------------------
    $reducir = true;
    $cont = 1;

    while ($reducir) {



        $iteracion = array();
        $iteracion = $iteraciones[$cont - 1];
        $reducibles = array();
        $matriz = array();
        $matriz = $iteracion['matriz'];
        //----------------------
        foreach ($iteracion['letras_ruta_critica'] as $key => $valor) {
            if ($matriz[$valor]['choque_periodo'] > 0 &&
                    $matriz[$valor]['duracion'] > $matriz[$valor]['duracion_choque'] && $valor != "") {
                $reducibles[$valor] = $matriz[$valor]['choque_periodo'];
            }
        }

        asort($reducibles);
        reset($reducibles);





        if (count($reducibles) > 0) {
            $existe = true;
            $anulados = array();
            while ($existe) {
                $matriz = $iteracion['matriz'];

                $matriz[key($reducibles)]['duracion'] = $matriz[key($reducibles)]['duracion'] - 1;

                calcular_tiempos($matriz, $matriz2, $iteracion['duracion_fin'], $num_columnas);
                obtener_ruta_critica($iteracion['letras_ruta_critica'], $iteracion['ruta_critica'], $matriz);

                $existe = true;
                foreach ($iteraciones[0]['ruta_critica'] as $key => $valor) {

                    if (!in_array($valor, $iteracion['ruta_critica'])) {
                        $existe = false;
                    }
                }

                if ($existe) {


                    $iteracion['actividad_reducido'] = key($reducibles);
                    $iteracion['anulado'] = $anulados;
                    $iteracion['matriz'] = $matriz;
                    crear_html($iteracion['html_tabla'], $iteracion['html_actividades'], $iteracion['html_enlaces'], $matriz2, $iteracion
                    );
                    $iteraciones[] = $iteracion;
                    break; //salimos del sub while
                } else {


                    $anulados[key($reducibles)] = $iteracion['ruta_critica'];
                    if (next($reducibles)) {
                        $existe = true;
                    } else {


                        $reducir = false; //false para salir del while
                        break; //para salir del sub while
                    }
                }
            }//fin de sub while
        }//fin de if
        else {

            $reducir = false;
        }
        $cont++;
    }//fin de while principal
    //------------------------------------------------------------------------------------
    //----------------------


    $cont = 1;
    $html_script = "";
    $html_script .= " <div id='tabs' style='width:" . ($num_columnas * 200 + 50) . "px;min-width:997px;'>
                <ul>";

    foreach ($iteraciones as $key => $valor) {
        $html_script .= "<li><a href='#tabs-" . $cont . "'>Iteracion " . $cont . "</a></li>    ";
        $cont++;
    }
    $html_script .= "</ul>";

    $cont = 1;
    foreach ($iteraciones as $key => $valor) {
        $html_script .= "
                <div id='tabs-" . $cont . "' style='position:relative;min-height:400px;width:100%;'>

                <div style='position:relative;'>
                    <div class='center_title_bar2'>Detalle de iteración</div>
                    <br /><br /><br />

				    " . $valor['html_tabla'] . "
                    </div>
                    <br />";
        //------------
        //Ruta critica
        $html_script .= " <div class='center_title_bar2'>Ruta crítica</div><br><br><br>";
        foreach ($valor['ruta_critica'] as $key2 => $value2) {
            $color = "";
            if (in_array($value2, $iteraciones[0]['ruta_critica']))
                $color = "color:#0796FF;";
            $html_script .= "<h3 style='" . $color . "'>" . implode(" - ", $value2) . "</h3>";
        }
        //------------
        $html_script .= "<div class='center_title_bar2'>Diagrama de actividades</div>
                    <br /><br /><br />

				  <div style='width:" . ($num_columnas * 250 + 50) . "px;min-width:997px;height:" . ($num_filas * 150 - 50) . "px;position:absolute;'>

                         " . $valor['html_enlaces'] . "

                          <div style='position:absolute;left:0px;top:0px;width:100%;height:inherit;'>
                          " . $valor['html_actividades'] . "
                          </div>

                    </div>

                    <div style='height:" . ($num_filas * 150 - 50) . "px;position:relative;'></div>
                    <br />";

        //------------------------------------------------
        //observaciones
        $html_script .= " <div class='center_title_bar2'>Observaciones: Actividades que no resultaron con la ruta critica principal</div><br>
					 <br>";
        if (count($valor['anulado']) > 0)
            foreach ($valor['anulado'] as $key2 => $valor2) {

                $html_script .= "<br>" . $key2;
                foreach ($valor2 as $key3 => $valor3) {
                    $html_script .= "<h3 style=''>" . implode(" - ", $valor3) . "</h3>";
                }
            } else
            $html_script .= "<br><h3 style=''>Sin observaciones</h3>";
        $html_script .= "<br>";
        //-------------------------------------------------


        $html_script .= "</div>    <!-- tab opcion " . $cont . " -->
          ";
        $cont++;
    }

    $html_script .= " </div><!-- tab -->  ";
    $html_script .= "<script language='javascript'>
				$( function() {
							var tabs = $( '#tabs' ).tabs();
							tabs.find( '.ui-tabs-nav' ).sortable({
							  axis: 'x',
							  stop: function() {
								tabs.tabs( 'refresh' );
							  }
							});
						  } );
				</script>";

    //costo total
    $costo_total = 0;
    $costo_total_reducido = 0;
    $costo_total_proyecto = 0;

    foreach ($iteraciones[0]['matriz'] as $actividad => $valor) {
        $costo_total = $costo_total + $valor['costo_normal'];
    }

    foreach ($iteraciones as $key => $valor) {
        $costo_total_reducido = $costo_total_reducido + $iteraciones[0]['matriz'][$valor['actividad_reducido']]['choque_periodo'];
    }
    $costo_total_proyecto = $costo_total + $costo_total_reducido;


    $html_script .= " <hr /> <div class='center_title_bar2'>Detalle del costo del proyecto</div>
                    <br /><br /><br />
					<table class='tabla-morado' style='width:50%;' >
					 <tr>
						<td>Descripción</td>
						<td >Costo S/.</td>
					  </tr>
					  <tr>
						<td>Costo Total normal: </td>
						<td align='right'>" . $costo_total . "</td>
					  </tr>
					  <tr>
						<td>Costo Total Reducido: </td>
						<td align='right'>" . $costo_total_reducido . "</td>
					  </tr>
					  <tr>
						<td>Costo total del Proyecto: </td>
						<td align='right'>" . $costo_total_proyecto . "</td>
					  </tr>
					</table>
					";
    //---------------------------------------------------
    //----------------------
    $html_script .= "<br /><br /> <div class='center_title_bar2'>Secuencia de reducciones</div>
                    <br /><br /><br />  ";
    $html_script .= "<table class='tabla-verde' style='' >
					 <tr>
						<td>Actividad</td>";
    for ($i = 0; $i < count($iteraciones); $i++) {
        $html_script .= "<td>Itera. " . ($i + 1) . "</td>";
    }
    $html_script .= "</tr>";
    foreach ($iteraciones[0]['matriz'] as $actividad => $valor) {
        $html_script .= "<tr><td align='center'>" . $actividad . "</td>";
        foreach ($iteraciones as $key => $valor2) {
            if ($actividad == $valor2['actividad_reducido'])
                $html_script .= "<td align='center'>X</td>";
            else
                $html_script .= "<td align='center'>&nbsp;</td>";
        }
        $html_script .= "</tr>";
    }
    $html_script .= "</table>";

    echo $html_script;

}
function test(){

  $arreglo = json_decode($_POST["arreglo"], true);


}

function obtener_nivel_fila($matriz2, $fila, $columna) {
    $existe = true;
    while ($existe) {
        $existe = false;
        foreach ($matriz2 as $actividad => $valor) {
            if ($valor['nivel_columna'] == $columna) {

                if ($valor['nivel_columna'] == $columna && $valor['nivel_fila'] == $fila) {

                    $fila++;
                    $existe = true;
                    break;
                }
            }
        }
    }
    return $fila;
}

function calcular_tiempos(&$matriz, $matriz2, &$duracion_fin, $num_columnas) {
    //Calculando los valores ES y EF
    $columna = 1;
    while ($columna <= $num_columnas) {
        foreach ($matriz as $actividad => $valor) {
            $max_ef = 0;
            if ($valor['nivel_columna'] == $columna) {

                foreach ($valor['predecesor'] as $key => $valor2) {
                    if ($matriz[$valor2]['ef'] > $max_ef)
                        $max_ef = $matriz[$valor2]['ef'];
                }
                $matriz[$actividad]['es'] = $max_ef;
                $matriz[$actividad]['ef'] = $max_ef + $matriz[$actividad]['duracion'];
            }
        }
        $columna++;
    }

    //Calculando los valores LS LF
    $duracion_fin = 0;
    foreach ($matriz2['fin']['predecesor'] as $key => $valor) {

        if ($matriz[$valor]['ef'] > $duracion_fin)
            $duracion_fin = $matriz[$valor]['ef'];
    }
    foreach ($matriz2['fin']['predecesor'] as $key => $valor) {

        $matriz[$valor]['lf'] = $duracion_fin;
        $matriz[$valor]['ls'] = $duracion_fin - $matriz[$valor]['duracion'];
    }
    $columna = $num_columnas;
    while ($columna >= 1) {
        foreach ($matriz as $actividad => $valor) {
            $min_ls = $duracion_fin;
            if ($valor['nivel_columna'] == $columna && $valor['sucesor'][0] != 'ninguno') {

                foreach ($valor['sucesor'] as $key => $valor2) {
                    if ($matriz[$valor2]['ls'] < $min_ls)
                        $min_ls = $matriz[$valor2]['ls'];
                }
                $matriz[$actividad]['lf'] = $min_ls;
                $matriz[$actividad]['ls'] = $matriz[$actividad]['lf'] - $matriz[$actividad]['duracion'];
            }
        }

        $columna--;
    }
}

function obtener_ruta_critica(&$letras_ruta_critica, &$rutas, $matriz) {


    $letras_ruta_critica = array();
    foreach ($matriz as $actividad => $valor) {
        if ($valor['es'] - $valor['ls'] == 0)
            $letras_ruta_critica[] = $actividad;
    }

    $matriz_ruta_critica = array();
    foreach ($matriz as $actividad => $valor) {
        if (in_array($actividad, $letras_ruta_critica)) {
            $matriz_ruta_critica[$actividad]['nivel_columna'] = $valor['nivel_columna'];
            foreach ($valor['predecesor'] as $key => $valor2) {
                if (in_array($valor2, $letras_ruta_critica))
                    $matriz_ruta_critica[$actividad]['predecesor'][] = $valor2;
            }
            foreach ($valor['sucesor'] as $key => $valor2) {
                if (in_array($valor2, $letras_ruta_critica))
                    $matriz_ruta_critica[$actividad]['sucesor'][] = $valor2;
            }
        }
    }



    $rutas = array();
    foreach ($matriz_ruta_critica as $actividad => $valor) {

        if ($valor['nivel_columna'] == 1)
            $rutas[] = array($actividad);
    }
    $cont_aux = count($rutas);
    while ($cont_aux > 0) {
        foreach ($rutas as $key => $valor) {
            $actividad = "";
            foreach ($valor as $key2 => $valor2) {
                $actividad = $valor2;
            }
            $cont = 1;
            $cont_aux = 0;
            $camino_ruta = array();
            if (count($matriz_ruta_critica[$actividad]['sucesor']) > 0)
                foreach ($matriz_ruta_critica[$actividad]['sucesor'] as $key2 => $valor2) {
                    if ($cont == 1) {
                        $camino_ruta = $rutas[$key];
                        $rutas[$key][] = $valor2;
                        $cont_aux++;
                    } else {
                        $nueva_ruta = array();
                        $nueva_ruta = $camino_ruta;
                        $nueva_ruta[] = $valor2;
                        $rutas[] = $nueva_ruta;
                        $cont_aux++;
                    }
                    $cont++;
                }
        }
    }


    return $rutas;
}

function crear_html(&$scripttabla, &$scriptact, &$scripenlaces, $matriz2, $iteracion) {


    $matriz = array();
    $matriz = $iteracion['matriz'];
    $scripttabla = "";
    $scripttabla .= "<table class='tabla-morado' style='width:98%;font-weight:bold;'>";
    $scripttabla .= "<tr>";
    $scripttabla .= "<td>Actividad</td>";
    $scripttabla .= "<td>Predecesor</td>";
    $scripttabla .= "<td>Sucesor</td>";
    $scripttabla .= "<td>Duración</td>";
    $scripttabla .= "<td>Duración choque</td>";
    $scripttabla .= "<td>Costo normal</td>";
    $scripttabla .= "<td>Costo choque</td>";
    $scripttabla .= "<td>Costo Choque x periodo</td>";
    $scripttabla .= "<td>Holguras (LS-ES)</td>";
    $scripttabla .= "</tr>";

    foreach ($matriz as $actividad => $valor) {
        $color = "";
        $fondo = "";
        $tamanio = "";
        if (in_array($actividad, $iteracion['letras_ruta_critica'])) {
            $color = "#0A3B6C"; //azul
            $fondo = "background-color:#E0D4E9;";
            if ($matriz[$actividad]['choque_periodo'] <= 0 ||
                    $matriz[$actividad]['duracion'] <= $matriz[$actividad]['duracion_choque'])
                $color = "#FF840E"; //naranja
        }
        if ($actividad == $iteracion['actividad_reducido']) {
            $color = "#657C0A"; //verde
            $tamanio = "font-size:16px;";
        }


        $scripttabla .= "<tr style='color:" . $color . ";" . $fondo . $tamanio . "'>";
        $scripttabla .= "<td>" . $actividad . "</td>";
        $scripttabla .= "<td>" . (implode(",", $valor['predecesor'])) . "</td>";
        $scripttabla .= "<td>" . (implode(",", $valor['sucesor'])) . "</td>";
        $scripttabla .= "<td>" . $valor['duracion'] . "</td>";
        $scripttabla .= "<td>" . $valor['duracion_choque'] . "</td>";
        $scripttabla .= "<td>" . $valor['costo_normal'] . "</td>";
        $scripttabla .= "<td>" . $valor['costo_choque'] . "</td>";
        $scripttabla .= "<td>" . $valor['choque_periodo'] . "</td>";
        $scripttabla .= "<td>" . $valor['ls'] . " - " . $valor['es'] . " = " . ($valor['ls'] - $valor['es']) . "</td>";
        $scripttabla .= " </tr>";
    }
    $scripttabla .= "</table>";


    //actividades
    $scriptact = "";
    foreach ($matriz2 as $actividad => $valor) {
        $clase_css = "";
        if ($valor['tipo'] == 'normal')
            $clase_css = "divContenedorAct";
        if ($valor['tipo'] == 'ficticio')
            $clase_css = "divContenedorActFict";
        if ($valor['tipo'] == 'temporal')
            $clase_css = "divContenedorTemp";
        if ($valor['tipo'] == 'fini')
            $clase_css = "divContenedorFini";
        if (in_array($actividad, $iteracion['letras_ruta_critica']))
            $clase_css = "divContenedorCritico";

        $scriptact .= "<div style='position:absolute;left:0px;top:0px;width:100%;height:inherit;'>";
        $scriptact .= "<div id='divAct" . $actividad . "'
					class='" . $clase_css . "'
					style='top:" . (($valor['nivel_fila'] - 1) * 150) . "px;left:" . (($valor['nivel_columna']) * 150) . "px'>";
        if ($valor['tipo'] != 'temporal' && $valor['tipo'] != 'fini') {
            $scriptact .= "<div class='divES'>
							<div style='height:40%;'></div>
							" . $matriz[$actividad]['es'] . "
						</div>
						<div class='divEF'>
							<div style='height:40%;'></div>
							" . $matriz[$actividad]['ef'] . "
						</div>
						<div class='divACT'>
							<div style='height:25%;'></div>
							" . $actividad . " = " . $matriz[$actividad]['duracion'] . "
						</div>
						<div class='divLS'>
							" . $matriz[$actividad]['ls'] . "
						</div>
						<div class='divLF'>
							" . $matriz[$actividad]['lf'] . "
						</div>
						</div>";
        } else {
            $scriptact .= "<div class='divES'>
							<div style='height:40%;'></div>&nbsp;&nbsp;
							&nbsp;
						</div>
						<div class='divEF'>
							<div style='height:40%;'></div>
							&nbsp;
						</div>
						<div class='divACT'>
							<div style='height:25%;'></div>
							" . ($valor['tipo'] == 'fini' ? ($actividad == 'ninguno' ? "Inicio" : "Fin") : "") . "
							" . ($valor['tipo'] == 'fini' ? ($actividad == 'ninguno' ? "ti=0" : "tf=" . $iteracion['duracion_fin']) : "") . "
						</div>
						<div class='divLS'>
							&nbsp;
						</div>
						<div class='divLF'>
							&nbsp;
						</div>
						</div>";
        }


        $scriptact .= "</div>";
    }



    global $num_filas,$num_columnas;
    			
    //enlaces
    $scripenlaces = "";	
    $scripenlaces .= "<div style='width:".($num_columnas * 200 + 50)."px;height:".($num_filas * 150 - 50)."px;position:absolute;'>";	
    $scripenlaces .= "<svg height='".($num_filas * 150 - 40)."px' width='".($num_columnas * 200 - 40)."px'>";			
    foreach($matriz2 as $actividad => $valor){
        if($valor['nivel_columna']>=1){
            foreach($valor['predecesor'] as $key => $valor2){
                $x1 = (($matriz2[$valor2]['nivel_columna']-0)*150 + ($matriz2[$valor2]['tipo']!='temporal'?100:0));
                $y1 = (($matriz2[$valor2]['nivel_fila']-1)*150 + 50);
                $x2 = (($matriz2[$actividad]['nivel_columna']-0)*150 + 0);
                $y2 = (($matriz2[$actividad]['nivel_fila']-1)*150 + 50);							
                $scripenlaces .= "<line id='ln".$actividad.$valor2."' name='ln".$actividad.$valor2."' ";
                $scripenlaces .= "x1='".$x1."' ";
                $scripenlaces .= "y1='".$y1."' ";
                $scripenlaces .= "x2='".$x2."' ";
                $scripenlaces .= "y2='".$y2."' "; 
                $scripenlaces .= "style='stroke:rgb(255,0,0);stroke-width:2;stroke-linecap:round' />";	
                if($valor['tipo']!='temporal')
                $scripenlaces .= "<polyline points='".($x2-5)." ".($y2-5)." ".($x2+1)." ".($y2)." ".($x2-5)." ".($y2+5)."' stroke='red' stroke-width='3'
stroke-linecap='round' fill='none' stroke-linejoin='round'/>";
                
            }
        }
    }
    $scripenlaces .= "</svg> </div>";
    
}

?>