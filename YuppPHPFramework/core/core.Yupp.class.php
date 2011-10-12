<?php

/**
 * Clase para acceder a la informacion del framework.
 */
class Yupp {

    function __construct()
    {
    }
    
    /**
     * Retorna el modo de ejecucion actual del framework, dev, prod o test.
     */
    public function getMode()
    {
       // En YuppConfig se definen los modos posibles, p.e. YuppConfig :: MODE_DEV
       return YuppContext :: getInstance()->getMode();
    }
    
    /**
     * Devuelve una lista con los nombres de las aplicaciones instaladas localmente.
     */
    public function getAppNames()
    {
       return PackageNames::getAppNames();
    }
    
    /**
     * Retorna una instancia de App para la aplicacion de nombre $name.
     * PRE: $name esta incluido en $this->getAppNames()
     */
    public function getApp( $name )
    {
       // TODO
    }
    
    public static function appExists( $appName )
    {
        return file_exists('apps/'.$appName);
    }
}

?>