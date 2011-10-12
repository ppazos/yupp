<?php

/**
 * Clase donde se manejan lso estandares de nombrado de archivos.
 * Sirve para sacar informacion de los nombres de los archivos y
 * volver a armar los nombres de los archivos a partir de la informacion.
 *
 * El estandar es: (paquete).(nombre).(tipo).php
 *
 * pauqete: nombre del paquete logico del archivo.
 * paquete: utils | core.files | core.datatypes | ...
 *
 * tipo: tipo de lo que contiene el archivo
 * tipo: class | interface | script | view | template | action | module | ...
 *
 * nombre: nombre del archivo
 * nombre: si tipo = class => es el nombre de la clase que contiene el archivo
 * nombre: si tipo = interface => es el nombre de la interfaz que contiene el archivo
 * nombre: si tipo = script => es el nombre de un script que contiene el archivo
 * nombre: si tipo = view => es el nombre de la vista o pagina (dinamica) que contiene el archivo
 * nombre: si tipo = action => es el nombre de la accion. La accion es una clase pero es un tipo particular y destacado de clase, por eso no se usa tipo=class
 *
 */
class FileNames {

    public static function getClassFilename( $package, $clazz )
    {
       // Si no me viene package, igual tiro el nombre bien.
       return (($package !== NULL && $package !=="")? $package . '.' : "") . $clazz . '.class.php';
    }

    public static function getInterfaceFilename( $package, $interface )
    {
       return $package . '.' . $interface . '.interface.php';
    }
    
    public static function getScriptFilename( $package, $interface )
    {
       return $package . '.' . $interface . '.script.php';
    }

    public static function getFilenameInfo( $filename )
    {
        $res = array();

        // paquete.nombre.tipo
        if ( preg_match("/(.*)\.(.*)\.(.*)\.php$/i", $filename, $matches) )
        {
            $res['package'] = $matches[1]; // FIXME: no se si es controller, mas bien es pacakge...
            
            if ( preg_match("/(.*)\.(.*)$/i", $res['package'], $matches_package) )
            {
               $res['app'] = $matches_package[1]; 
            }
            
            $res['name'] = $matches[2];
            $res['type'] = $matches[3];
            return $res;
        }

        // nombre.tipo
        if ( preg_match("/(.*)\.(.*)\.php$/i", $filename, $matches) )
        {
            $res['name'] = $matches[1];
            $res['type'] = $matches[2];
            return $res;
        }

        // nombre
        if ( preg_match("/(.*)\.php$/i", $filename, $matches) )
        {
            $res['name'] = $matches[1];
            return $res;
        }

        //throw new Exception("utils.FileNames.class::getFileInfo() : el nombre $filename no respeta el estandar");
        return false; // No tiro except xq me tranca todo. Si tengo un archivo que no respeta el estandar de nombres, no lo considero. Esto me deja poder tener archivos que no respetan los nombres en los directorios donde se buscan archivos con cierto nombre, sin que de error.
    }
}

?>