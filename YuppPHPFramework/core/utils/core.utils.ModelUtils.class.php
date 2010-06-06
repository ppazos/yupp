<?php

// Depende de clase FileSystem xq tiene que leer los archivos
// de un directorio dado para levantar clases (include de esos archivos).

// TODO: Una clase FileSystem que sepa hacer funciones sobre directorios
// y archivos (ver la clase en SWP_CMS)

/*
 * Created on 24/02/2008
 */

// FIXME: usar YuppLoader
// Con Loader me da un error de que no encuentra a la clase core.config.FileNames en YuppLoader...
//YuppLoader::load("core",'FileSystem');
//YuppLoader::load("core.config",'PackageNames');
//YuppLoader::load("core.persistent",'PersistentObject');
include_once('core/core.FileSystem.class.php');
include_once('core/config/core.config.PackageNames.class.php');
include_once('core/persistent/core.persistent.PersistentObject.class.php');

//YuppLoader::load("core.persistent", "PersistentObject");

class ModelUtils {

    /**
     * Devuelve la lista de los nombres de todas las clases de modelo.
     */
    public static function getModelClasses()
    {
    	  // el directorio del modelo es fijo, pero lo tengo que sacar de una configuracion, no hardcoded aca.
        $components = $packNames->getComponentNames();
        
        $classNames = array();
        foreach ( $components as $component )
        {
            $package = "$component/model"; // TODO: si hay subdirectorios, devolverlos tambien.
            $model_path = YuppConventions::getModelPath( $package );
            //$model_path = YuppConventions::getModelPath( $component );
            $_classNames = FileSystem::getFileNames($model_path, PackageNames::MODEL_FILE_REGEXP, array( PackageNames::MODEL_CLASS_GROUP )); // Todos los php del paquete utils, idem anterior, ahora sin el "utils."
            $classNames = array_merge($classNames, $_classNames);
        }
        
        // LEvanta el directorio y los nombres de las clases.
        // Grupo 1 es el nombre del componente
        // Grupo 2 es el nombre de la clase

        // TODO: LA REGEXP DEL PAQUETE DE MODELO ME LO DEBERIA DAR UNA CLASE RESPONSABLE DE SABER CUALES SON LAS REGEXPS DE LOS PAQUETES DESTACADOS Y DADO UNA RUTA DE PAQUETES SABER DE QUE TIPO ES (modulos, core, modelo, vistas, acciones, etc.)

        // Los nombres estan codificados segun algun estandar (a definir) y se puede sacar el nombre de la clase del nombre del archivo.
        // NOMBRES: modelDir/componente.ClassName.php

        return $classNames;
    }

    /**
     * getSubclassesOf
     * Devuelve una lista de nombres de clases hijas de clazz.
     * Por ejemplo, si clazz es PersistentObject, da todas las clases de primer nivel del modelo definido.
     * 
     * @param string $clazz nombre de una clase de modelo (tambien puede ser PersistentObject).
     * 
     */
    public function getSubclassesOf( $clazz )
    {
        // chekear el class loader, viendo de las clases cargadas cuales son hijas directas de $clazz.

        // Esto en realidad se deberia hacer con getLoadedModelClasses
        // porque ModelUtils es para resolver temas de las clases del modelo.
        //$loadedClasses = YuppLoader::getLoadedClasses();
        $loadedClasses = YuppLoader::getLoadedModelClasses();
        
        //print_r ( YuppLoader::getLoadedClasses() ); // tiene claves class, filename y package
        //echo "<br/>";
        //print_r ( YuppLoader::getLoadedModelClasses() ); // solo tiene class sin clave
        
        $res = array();

        /* Con getLoadedModelClasses se obtiene un array de nombres de clases, no classInfo
        foreach ( $loadedClasses as $classInfo ) // Si la clase cargada tiene como padre a clazz, es subclase de clazz.
        {
            // class info tiene: package, class y filename.
        	   if ( get_parent_class( $classInfo['class'] ) == $clazz ) $res[] = $classInfo['class'];
        }
        */
        
        foreach ( $loadedClasses as $loadedClass ) // Si la clase cargada tiene como padre a clazz, es subclase de clazz.
        {
            if ( get_parent_class( $loadedClass ) == $clazz ) $res[] = $loadedClass;
        }

        return $res;
    }

    /**
     * clazz es el nombre de una clase de modelo (tambien puede ser PersistentObject).
     * Devuelve una estructura multiple con los nombres de todas las clases que heredan de clazz (hijas, nietas, etc)
     */
    public function getAllSubclassesOf( $clazz )
    {
        //echo "<h1>ModelUtils.getAllSubclassesOf $clazz</h1>";
        
    //Logger::struct( get_declared_classes(), "Declared classes ".__FILE__." ".__LINE__ );
        
        // Esto en realidad se deberia hacer con getLoadedModelClasses
        // porque ModelUtils es para resolver temas de las clases del modelo.
        //$loadedClasses = YuppLoader::getLoadedClasses();
        $loadedClasses = YuppLoader::getLoadedModelClasses();
        $res = array();

        /* Con getLoadedModelClasses se obtiene un array de nombres de clases, no classInfo
        foreach ( $loadedClasses as $classInfo ) // Si la clase cargada tiene como padre a clazz, es subclase de clazz.
        {
            // class info tiene: package, class y filename.
            //echo "1: " . $classInfo['class'] . "<br/>";
            //echo "2: " . $clazz . "<br/>";
            //echo "ClassInfo<br/>";
            //print_r( $classInfo );
            //echo "ClassInfo.class: " . $classInfo['class'] . ", ". $clazz ."<br/>";

            if ( is_subclass_of( $classInfo['class'], $clazz ) ) $res[] = $classInfo['class'];
        }
        */
        
        foreach ( $loadedClasses as $loadedClass ) // Si la clase cargada tiene como padre a clazz, es subclase de clazz.
        {
            if ( is_subclass_of( $loadedClass, $clazz ) ) $res[] = $loadedClass;
        }

        return $res;
    }

    /**
     * Devuelve una lista con todos los nombres de los ancestros de clazz hasta PersistentObject.
     * Obs: devuelve las clases en orden de herencia, desde la clase de nivel 1 (hereda directamente de PO) a la ultima subclase. (*)
     */
    //public function &getAllAncestorsOf( $clazz )
    public function getAllAncestorsOf( $clazz )
    {
        $res = array();
        $parent = get_parent_class( $clazz ); // tiene solo un parent...
        while ( $parent != NULL && $parent != "" && $parent !== 'PersistentObject' )
        {
            //$res[] = $parent; // agrega al final
            array_unshift( $res, $parent ); // agrega al principio, asi sale la superclase de todos en el primer lugar. (*)
            $parent = get_parent_class( $parent );
        }
        return $res;
    }
}

?>
