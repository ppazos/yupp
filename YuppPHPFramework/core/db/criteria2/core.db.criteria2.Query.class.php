<?php
class Query
{
   // FIXME: todas las palabras clave de SQL deben ser pedidas a la clase XXXDatabase configurada para acceso a datos.
   
	private $select = array (); // Opcional    (Projection) // Si es vacio corresponde un '*' en la evaluacion.
	private $from   = array (); // Obligatorio (From)
	private $where  = NULL;     // Opcional    (Condition)

	// Arrays de cada cosa, puedo tener varios ordenamientos.
   private $order = array();
	//private $order_alias = array ();
	//private $order_attr  = array ();
	//private $order_dir   = array ();

	private $limit_max;
	private $limit_offset;

	// http://www.1keydata.com/sql/sqldistinct.html

	//Ejemplo con agregacion y groupBy:
	//
	//SELECT "column_name1", SUM("column_name2")
	//FROM "table_name"
	//GROUP BY "column_name1"
	//
	//Ejemplo con between:
	//
	//SELECT "column_name"
	//FROM "table_name"
	//WHERE "column_name" BETWEEN 'value1' AND 'value2'
	//
	//Ejemplo distinct:
	//
	//SELECT DISTINCT "column_name"
	//FROM "table_name"
	//
	//Count + Distinct
	//
	//SELECT COUNT(DISTINCT store_name)
	//FROM Store_Information

   /**
    * Constructor.
    */
	function Query()
	{
	}

	//public function addProjection( Projection $p )
	public function addProjection($alias, $attr)
	{
		// TODO:
		// CHECK CORRECTITUD: proyeccion debe tener aliases presentes en el from. Necesario agregar primero el FROM.

		$p = new stdClass(); // Objeto anonimo.
		$p->alias = $alias;
		$p->attr = $attr;

		$this->select[] = $p;

		return $this;
	}

	public function addFrom($tableName, $alias)
	{
		// TODO:
		// CHECK ALIAS: No existe ya ese alias.

		$table = new stdClass(); // Objeto anonimo.
		$table->name = $tableName;
		$table->alias = $alias;

		$this->from[] = $table;

		return $this;
	}

	// TODO: podria permitir modificar la cond internamente y desde afuera con AND u OR para no tener que armar toda la condicion afuera.
	public function setCondition(Condition $cond)
	{
		$this->where = $cond;
	}

	public function setLimit($max, $offset)
	{
		$this->limit_max = $max;
		$this->limit_offset = $offset;
	}


   /**
    * 
    * 
    * @param alias alias de la tabla
    * @param attr nombre del atributo al que se hace referencia
    * @param dir direccion en la que se quiere ordenar "asc" o "desc"
    */
	public function addOrder($alias, $attr, $dir)
	{
		// TODO: CHEK ALIAS existe? => necesidad de agregar FROM antes.
      
      $order = new stdClass(); // Objeto anonimo.
      $order->attr = $attr;
      $order->alias = $alias;
      $order->dir = $dir;
      
      $this->order[] = $order;

      return $this;
	}

	public function evaluate()
	{
		$select = $this->evaluateSelect() . " ";
		$from   = $this->evaluateFrom() . " ";
		$where  = $this->evaluateWhere() . " ";
		$order  = $this->evaluateOrder() . " ";
		$limit  = "";

		return $select . $from . $where . $order . $limit;
	}

	private function evaluateSelect()
	{
		if (count($this->select) == 0)
		{
			return "SELECT *";
		}
		else
		{
			$res = "SELECT ";
			foreach ($this->select as $proj)
			{
				$res .= $proj->alias . "." . $proj->attr . ", "; // Projection
			}
			return substr($res, 0, -2); // Saca ultimo "; "
		}
	}

	private function evaluateFrom()
	{
		if (count($this->from) == 0)
		{
			// ERROR! es olbigatorio por lo menos una!
			throw new Exception("FROM no puede ser vacio");
		}
		else
		{
			$res = "FROM ";
			foreach ($this->from as $table)
			{
				$res .= $table->name . " " . $table->alias . ", ";
			}
			return substr($res, 0, -2); // Saca ultimo "; "
		}
	}

	private function evaluateWhere()
	{
		if ($this->where !== NULL)
		{
			return "WHERE " . $this->where->evaluate();
		}
	}
   
   private function evaluateOrder()
   {
      if (count($this->order) > 0)
      {
         $res = "ORDER BY ";
         foreach ($this->order as $order)
         {
            $res .= $order->alias . "." . $order->attr . " " . $order->dir . ", ";
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
   }

}
?>