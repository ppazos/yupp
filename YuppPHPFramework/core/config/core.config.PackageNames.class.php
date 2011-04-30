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
    
    // Para sacar la app del package de un modelo.
    // PRE: isModelPackage( $package )
    public static function getModelPackageApp( $package )
    {
         preg_match(self::MODEL_PACKAGE_REGEXP, $package, $matches);
        return $matches[3];
    }

//    public function getModelPackagePath( $app )
//    {
//         return "./apps/$app/model";
//    }


   /**
    * getAppNames
    * Devuelve una lista de los nombres de las apps
    * existentes en la instalacion de Yupp (lee el filesystem).
    */
   public static function getAppNames()
   {
      $appNames = array();
      
      $dir = dir('./apps');
      while (false !== ($app = $dir->read()))
      {
         if ( !String::startsWith( $app, "." ) && $app !== "core" && is_dir('./apps/'.$app))
         {
            $appNames[] = $app;
         }
      }
      
      return $appNames;
   }
}
?>