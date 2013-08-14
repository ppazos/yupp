<?php

// RNE: si Select tiene funciones agregadas como: AVG, MAX, MIN, SUM, COUNT,
//      y atributos en la proyeccion, la consulta debe tener un group by los atributos.
// TODO: COUNT DISTINCT x.y

class Select {

    private $projections = array(); // Se inicializa esta sola porque es la mas comun.

    function __construct() {}

    /**
     * Agrega un item al select de la consulta. 
     */
    public function add( SelectItem $item )
    {
       $this->projections[] = $item;
    }
    
    /**
     * Obtiene todos los items del select.
     */
    public function getAll()
    {
       return $this->projections;
    }
    
    /**
     * Devuelve true si no tiene items.
     */
    public function isEmpty()
    {
       return sizeof($this->projections) == 0;
    }
}

class SelectItem {
   private $itemAlias; // modela SELECT item AS itemAlias
   
   public function setAlias($alias)
   {
      $this->itemAlias = $alias;
   }
   
   public function getAlias()
   {
      return $this->itemAlias;
   }
}
class SelectAttribute extends SelectItem {
   private $tableAlias;
   private $attrName;
   public function __construct($tableAlias, $attrName)
   {
      $this->tableAlias = $tableAlias;
      $this->attrName = $attrName;
   }
   public function getTableAlias()
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
   private $name;
   private $param; // SelectItem
   
   const AGTN_COUNT = "count";
   const AGTN_AVG = "avg";
   const AGTN_MAX = "max";
   const AGTN_MIN = "min";
   const AGTN_SUM = "sum";
   const AGTN_DISTINTC = "distinct";
   
   public function __construct($aggregationName, SelectItem $param)
   {
      $this->name = $aggregationName;
      $this->param = $param;
   }
//   public function setParam( SelectItem $param )
//   {
//      $this->param = $param;
//   }

   public function getName()
   {
      return $this->name;
   }
   
   public function getParam()
   {
      return $this->param;
   }
}

?>