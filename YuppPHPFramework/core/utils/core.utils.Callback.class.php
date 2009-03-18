<?php
/*
 * Created on 02/01/2008
 *
 */

// ver: http://www.php.net/manual/es/language.pseudo-types.php
//      http://www.php.net/manual/es/language.pseudo-types.php#language.types.callback

/**
 * Registra funciones para ser ejecutadas en otro momento de forma secuencial.
 * Son regristrados objetos, metodos y parametros de dichos metodos, los cuales
 * pueden ser tambien Callbacks y deben resolverse recursivamente para llegar
 * a un valor simple para poder pasarlo como parametro al metodo.
 */
class Callback {

    /* GUARDA UNA ESTRUCTURA COMO LA SIGUIENTE:
     * $callback_row = array( 'obj' => $c,
                       'method' => 'setA_id',
                       'par1' => array( 'obj' => $a,
                                        'method' => 'getId' ),
                       'par2' => 24 );
     */

    const OBJ_KEY          = 'obj';
    const METHOD_KEY       = 'met';
    const PARAM_PREFIX_KEY = 'par';

    private $callback = array(); // Estructura con toda la informacion necesaria para llamar a determinado metodo de determinada clase y parametros simples u parametros de callback.
    // Lista de callbacks a hacer secuencialmente (PARA HACER UNA LISTA NECESITO OTRA CLASE... ESTA HACE SOLO UNA LLAMADA...)

    /*
    public function add( Callback $cb )
    {
        $this->callbackList[] = $cb;
    }
    */

    public function set( $obj, $method, $parList )
    {
        $this->callback[self::OBJ_KEY] = $obj;
        // TODO: podria verificar que el objeto no es null
        // TODO: podria verificar que el metodo existe en el objeto
        $this->callback[self::METHOD_KEY] = $method;

        // TODO: parList debe ser un array
        foreach ( $parList as $i => $param )
        {
            $this->callback[self::PARAM_PREFIX_KEY . $i] = $param; // las keys son par0, par1, par2...
        }
    }

    public function paramCount()
    {
        return sizeof( $this->callback ) - 2;
    }

    public function getParam( $i )
    {
        // TODO: Chekear indices
        return $this->callback[ self::PARAM_PREFIX_KEY . $i ];
    }

    public function getObject()
    {
        return $this->callback[ self::OBJ_KEY ];
    }

    public function getMethod()
    {
        return $this->callback[ self::METHOD_KEY ];
    }

    //
    public function execute()
    {
        //echo "<hr/>";

        // REsuelvo parametros que tengan llamadas y obtengo valores simples...
        $cantParams = $this->paramCount(); // Parametros para procesar
        //echo "Params: $cantParams <br/>";

        $resolvedParams = array();
        while ($cantParams > 0)
        {
            $param = $this->getParam( $cantParams-1 ); // si hay par1, par2, par3, primero me da el par3, luego el par2... etc.

            // Si el atributo es Callback, se resuelve recursivamente.
            $rparam = NULL;

            //echo "A: class=" . get_class($param) . " val=" . $param . "<br/>";
            //echo "B: " . get_class($this) . "<br/>";
            //echo "C: " . ( get_class($param) === get_class($this) ) . "<br/>";

            if ( get_class($param) === get_class($this) ) $rparam = $param->execute( $param ); // Veo si el param es tambien un callback y obtengo el valor simple
            else $rparam = $param;

            $resolvedParams[($cantParams-1)] = $rparam;
            $cantParams --;
        }

        $obj = $this->getObject();
        $met = $this->getMethod();

        $scall  = 'call_user_func( array($obj,$met), ';
        foreach ( $resolvedParams as $param )
        {
            $scall .= $param . ', ';
        }
        $scall = substr( $scall, 0, strlen($scall)-2 ); // saca la ultima coma
        $scall .= ');';

        //echo "XXX: " . $scall . "<br/>";

        $res = NULL;
        eval( '$res = ' . $scall );

        //echo "RES: " . $res . "<br />";
        return $res;
    }

    public function __toString()
    {
        return get_class($this) . " obj: " . get_class($this->callback[self::OBJ_KEY]) . " method: " . $this->callback[self::METHOD_KEY];
    }
}

?>
