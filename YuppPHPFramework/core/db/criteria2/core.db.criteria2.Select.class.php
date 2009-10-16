<?php

// RNE: si Select tiene funciones agregadas como: AVG, MAX, MIN, SUM, COUNT,
//      y atributos en la proyeccion, la consulta debe tener un group by los atributos.

class Select {

    private $projections = array(); // Se inicializa esta sola porque es la mas comun.

//    private $functionSetted = false; // Para saber si se seteo algo mas que proyecciones.

    // Elige columnas distintas, sirve para sacar dupicados (como cuando se quieren listar todos los nombres distintos).
//    private $distinct = NULL;
    
    // Sirve para contar los distintos, es como un distinc y un count juntos. 
//    private $countDistinct = NULL;
    
    // Agregaciones
//    private $count = NULL;
//    private $avg = NULL;
//    private $min = NULL;
//    private $max = NULL;
//    private $sum = NULL;
//
//    private $lower = NULL;
//    private $upper = NULL;

    function Select() {
    }

    public function add( SelectItem $item )
    {
       $this->projections[] = $item;
    }
    public function getAll()
    {
       return $this->projections;
    }
    public function isEmpty()
    {
       return sizeof($this->projections === 0);
    }

    // EVALUATE va en cada evaluador de DBMS.
    /*
    private function addTo( $arr, $alias, $attr )
    {
       $p = new stdClass(); // Objeto anonimo.
       $p->attr  = $attr;
       $p->alias = $alias;

       $arr[] = $p;
    }

    / **
     * @param $func funcion a aplicar a cada proyeccion de $arr.
     * @param $arr array con proyecciones. Debe tener por lo menos un elemento.
     * /
    private function evaluateFunction( $func, $arr )
    {
       $res = "";
       foreach ($arr as $c)
       {
          $res .= $func . "(" . $c->alias . "." . $c->attr . "), ";
       }
       //return substr($res, 0 , -2);
       return $res;
    }

    // PROJECTION //
    public function addProjection($alias, $attr)
    {
       $this->addTo( &$this->projections, $alias, $attr );
    }

    public function evaluateProjection()
    {
       if ( count($this->projections) == 0 )
       {
          // Si los demas atributos son NULL deberia tirar *
          if (!$this->functionSetted) return "*";
          return "";
       }

       $res = "";
       foreach ($this->projections as $p)
       {
          $res .= $p->alias . "." . $p->attr . ", ";
       }
       //return substr($res, 0 , -2);
       return $res;
    }
    // / PROJECTION //

    // AVG //
    public function addAvg($alias, $attr)
    {
       $this->functionSetted = true;
       if ( $this->avg === NULL ) $this->avg = array();
       $this->addTo( &$this->avg, $alias, $attr );
    }

    public function evaluateAvg()
    {
       if ($this->avg === NULL) return "";
       return $this->evaluateFunction( "AVG", $this->avg );
    }
    // / AVG //

    // COUNT //
    public function addCount($alias, $attr)
    {
       $this->functionSetted = true;
       if ( $this->count === NULL ) $this->count = array();
       $this->addTo( &$this->count, $alias, $attr );
    }

    public function evaluateCount()
    {
       if ($this->count === NULL) return "";
       return $this->evaluateFunction( "COUNT", $this->count );
    }

    // /COUNT //

    public function evaluate()
    {
    	 $res = $this->evaluateProjection() .
              $this->evaluateCount() .
              $this->evaluateAvg();

       return substr($res, 0 , -2);
    }
*/
}

class SelectItem {
}
class SelectAttribute extends SelectItem {
   private $tableAlias;
   private $attrName;
   public function __construct($tableAlias, $attrName)
   {
      $this->tableAlias = $tableAlias;
      $this->attrName = $attrName;
   }
   public function getAlias()
   {
      return $this->tableAlias;
   }
   public function getAttrName()
   {
      return $this->attrName;
   }
}
class SelectFunction extends SelectItem {
   private $functionName;
   private $param; // SelectItem
   
   const FUNCTION_LOWER = "lower";
   const FUNCTION_UPPER = "upper";
   
   public function __construct($functionName, SelectItem $param)
   {
      $this->functionName = $functionName;
      $this->param = $param;
   }
//   public function setParam( SelectItem $param )
//   {
//      $this->param = $param;
//   }
}
class SelectAggregation extends SelectItem {
   private $aggregationName;
   private $param; // SelectItem
   
   const AGTN_COUNT = "count";
   const AGTN_AVG = "avg";
   const AGTN_MAX = "max";
   const AGTN_MIN = "min";
   const AGTN_SUM = "sum";
   const AGTN_DISTINTC = "distinct";
   
   public function __construct($aggregationName, SelectItem $param)
   {
      $this->aggregationName = $aggregationName;
      $this->param = $param;
   }
//   public function setParam( SelectItem $param )
//   {
//      $this->param = $param;
//   }
}

?>