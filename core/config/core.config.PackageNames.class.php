<?php

// TODO: Tal vez tendria que mergear esta clase con FileNames...

class PackageNames {

    //private $modelPackageRegExp = "/(.*)\.model\.(.*)\.class\.php$/i";
    const MODEL_FILE_REGEXP    = '/(((.*)\.)?model)\.(.*)\.class\.php$/i';
    const MODEL_PACKAGE_GROUP  = 1; // No se utiliza!
    const MODEL_CLASS_GROUP    = 4;

    // Solo se usa localmente
    //const MODEL_PACKAGE_REGEXP = '/(((.*)\.)?model)$/i';
    const MODEL_PACKAGE_REGEXP = '/(((.*)\.)?model(\.(.*))?)$/i'; // Correcion para poder poner subdirectorios en /model


    public static function isModelPackage( $package )
    {
    	  return preg_match(self::MODEL_PACKAGE_REGEXP, $package);
    }
    
    // Para sacar el componente del package de un modelo.
    // PRE: isModelPackage( $package )
    public static function getModelPackageComponent( $package )
    {
    	  preg_match(self::MODEL_PACKAGE_REGEXP, $package, $matches);
        return $matches[3];
    }

//    public function getModelPackagePath( $component )
//    {
//    	  return "./apps/$component/model";
//    }


   /**
    * getComponentNames
    * Devuelve una lista de los nombres de los componentes 
    * existentes en la instalacion de Yupp (lee el filesystem).
    */
   public static function getComponentNames()
   {
      $componentNames = array();
      
    	$dir = dir('./apps');
      while (false !== ($component = $dir->read()))
      {
         if ( !String::startsWith( $component,"." ) && $component !== "core" && is_dir('./apps/'.$component))
         {
            $componentNames[] = $component;
         }
      }
      
      return $componentNames;
   }
}
?>