<?php

// Tal vez tendria que mergear esta clase con FileNames...

class PackageNames {

    // TODO: que los campos sean constantes y las funciones estaticas.

    //private $modelPackageRegExp = "/(.*)\.model\.(.*)\.class\.php$/i";
    private $modelFileRegExp = "/(((.*)\.)?model)\.(.*)\.class\.php$/i";
    private $modelPackageGroup = 1;
    private $modelClassGroup = 4;

    private $modelPackageRegExp = "/(((.*)\.)?model)$/i";

    //private $modelPackagePath = "./model"; // ruta fisica al paquete a partir de la raiz del sistema.

    public function PackageNames() {
    }

    public function getModelFileRegExp()
    {
        return $this->modelFileRegExp;
    }

    public function getModelPackageRegExp()
    {
    	  return $this->modelPackageRegExp;
    }

    public function isModelPackage( $package )
    {
    	  return preg_match($this->modelPackageRegExp, $package);
    }
    
    // Para sacar el componente del package de un modelo.
    // PRE: isModelPackage( $package )
    public function getModelPackageComponent( $package )
    {
    	  preg_match($this->modelPackageRegExp, $package, $matches);
        return $matches[3];
    }

    public function getModelPackageGroup() { return $this->modelPackageGroup; }
    public function getModelClassGroup()   { return $this->modelClassGroup; }


//    public function getModelPackagePath( $component )
//    {
//    	  return "./components/$component/model";
//    }


   /**
    * getComponentNames
    * Devuelve una lista de los nombres de los componentes 
    * existentes en la instalacion de Yupp.
    */
   public static function getComponentNames()
   {
      $componentNames = array();
      
    	$dir = dir("./components");
      while (false !== ($component = $dir->read()))
      {
         if ($component !== "." && $component !== ".." && $component !== "core")
         {
            $componentNames[] = $component;
         }
      }
      
      return $componentNames;
   }
}
?>