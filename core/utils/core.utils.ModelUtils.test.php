<?php

// Depende de clase FileSystem xq tiene que leer los archivos
// de un directorio dado para levantar clases (include de esos archivos).

// TODO: Una clase FileSystem que sepa hacer funciones sobre directorios
// y archivos (ver la clase en SWP_CMS)

/*
 * Created on 24/02/2008
 *
 */

include_once('core.utils.ModelUtils.class.php');

echo "<pre>";
print_r( ModelUtils::getModelClasses() );
echo "</pre>";

?>
