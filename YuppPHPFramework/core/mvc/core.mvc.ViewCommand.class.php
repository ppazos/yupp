<?php

class ViewCommand {

    const EXECUTE_COMMAND = 1; // Ejecutar otra accion
    const DISPLAY_COMMAND = 2; // Mostrar un view
    const STRING_DISPLAY_COMMAND = 3; // Mostrar un string (json, html, xml, etc) (se usa para requests ajax)

    private $command;

    // Command execute
    private $component;
    private $controller;
    private $action;

    // Command display
    private $pagePath; // path a pagina fisica o id de pagina logica (es un id aparte del id entero).
    private $viewName; // Nombre de la pagina...

    private $flash  = array(); // Copia el flash del controller para transportarlo al modelo.
    private $params = array(); // Modelo para display o params para execute.
                               // Obs: execute tiene solo datos simples, para display puede tener
                               // tambien instancias del modelo, datos estructurados y demas.
                               // Para execute seria como los mismos params que vienen de la web, un mapa de strings...


    private $_string; // Para display_string

    public function isExecuteCommand() { return ($this->command == self::EXECUTE_COMMAND); }
    public function isDisplayCommand() { return ($this->command == self::DISPLAY_COMMAND); }
    public function isStringDisplayCommand() { return ($this->command == self::STRING_DISPLAY_COMMAND); }

    public function component()  { return $this->component; }
    public function controller() { return $this->controller; }
    public function action()     { return $this->action; }

    public function viewName()   { return $this->viewName; }
    public function pagePath()   { return $this->pagePath; }

    public function params()     { return $this->params; }
    public function param($name) { return $this->params[$name]; }
    
    public function flash($name = NULL)
    {
      //print_r($this->flash);
      //echo (($name === NULL) ? "A" : "B");
      //return (($name === NULL) ? $this->flash : $this->flash[$name]);
      if ($name === NULL) return $this->flash;

      return $this->flash[$name];
    }
    
    public function getString()
    {
       return $this->_string;
    }

    /*
     * Ejecutar una accion de un controller (DEBERIA SER DEL COMPONENTE ACTUAL!!!!)
     * Se ejecuta desde el controller.
     */
    public static function execute($component, $controller, $action, $params, $flash)
    {
       $c             = new ViewCommand();
       $c->command    = self::EXECUTE_COMMAND;
       $c->component  = $component;
       $c->controller = $controller;
       $c->action     = $action;
       $c->params     = $params;
       $c->flash      = $flash;
       return $c;
    }

    /*
     * Para mostrar una pagina del componente actual.
     * Se ejecuta desde el controller.
     */
    //public static function display($viewName, &$params, &$flash)
    public static function display($viewName, $params, $flash)
    {
       $c           = new ViewCommand();
       $c->viewName = $viewName;
       $c->command  = self::DISPLAY_COMMAND; // VERIFICAR que es un comando valido.
       $c->params   = $params;
       $c->flash    = $flash;
       return $c;
    }
    
    public static function display_string($string)
    {
       $c          = new ViewCommand();
       $c->_string = $string;
       $c->command = self::STRING_DISPLAY_COMMAND; // VERIFICAR que es un comando valido.

       return $c;
    }

    /*
     * Se setea en el RequestManager!
     */
    public function setPagePath($pagePath)
    {
        $this->pagePath = $pagePath;
    }

    public function show()
    {
       echo "<pre>";
       print_r( $this->flash );
       print_r( $this->params );
       echo "</pre>";
    }
}
?>