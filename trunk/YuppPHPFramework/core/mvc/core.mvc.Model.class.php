<?php

/*
 * Singleton no persistente.
 * Representa el modelo que se le pasa al view.
 */
class Model {

    private static $instance = NULL;
    private $model = array();
    private $flash = array(); // luego podria ir en otro lugar...

    private function __construct()
    {
    }

    public static function getInstance()
    {
       if (self::$instance == NULL) self::$instance = new Model();
       return self::$instance;
    }

    public function setModel( $params )
    {
        $this->model = $params;
    }

    public function add($key, $value)
    {
        $this->model[$key] = $value;
    }

    public function get($key)
    {
       if ( isset($this->model[$key]) ) return $this->model[$key];
       return NULL;
    }
    
    /**
     * Devuelve todo el modelo (sin flash).
     */
    public function getAll()
    {
       return $this->model;
    }

    // FLASH ==========================================
    
    public function setFlash( $params )
    {
       $this->flash = $params;
    }
    public function flash($key)
    {
       if ( isset($this->flash[$key]) ) return $this->flash[$key];
       return NULL;
    }
    
    public function addFlash( $params )
    {
       foreach ($params as $key => $value)
       {
           $this->flash[$key] = $value;
       }
    }

    //

    /**
     * Como esta clase es singleton, pasa que los valores permanecen almacenados.
     * Esta funcion sirve para cuando se termina el request, borrar todo valor del Model.
     */
    public function reset()
    {
        $this->flash = array();
       $this->model = array();
    }

    public function show()
    {
       echo "<pre>";
       print_r( $this->flash );
       print_r( $this->model );
       echo "</pre>";
    }
}
?>