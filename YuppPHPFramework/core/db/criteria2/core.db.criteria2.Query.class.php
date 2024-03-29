<?php

YuppLoader::load( "core.db.criteria2", "Select" );

class Query
{
   // FIXME: todas las palabras clave de SQL deben ser pedidas a la clase XXXDatabase configurada para acceso a datos.
   
   private $select; // Select
   
	//private $select = array (); // Opcional    (Projection) // Si es vacio corresponde un '*' en la evaluacion.
	private $from   = array (); // Obligatorio (From)
	private $where  = NULL;     // Opcional    (Condition)

	// Arrays de cada cosa, puedo tener varios ordenamientos.
   // Order: alias, atributo, direccion (ASC,DESC)
   private $order = array();
   
   // Nombres de los elementos en groupBy
   private $groupBy = array();

	private $limit_max;
	private $limit_offset = 0;
   
   // Necesario para SQLServer, si una query pagina, necesito saber sobre que tabla se
   // hace la paginacion, porque en el from de la query pueden haber tablas para varias
   // clases, y para armar la query SQL se usa un nombre de tabla para una sola clase.
   private $limit_table;

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

   // Cantidad de logs a paginas distintas contados por pagina
   // SELECT `to_id` , COUNT( `to_id` )
   // FROM `portal_log_page_access`
   // GROUP BY `to_id`

	public function __construct()
	{
      $this->select = new Select();
	}

   // ========================================================
	// Select alias.attr, alias.attr, ...
   // TODO: select podria incluir:
   //        - funciones sobre atributos: lower(alias.attr)
   //        - agregaciones: count(alias.attr), sum(), max()
   //
   /**
    * Agrega una proyeccion sobre las columnas de las tablas seleccionadas para la respuesta de la consulta.
    */
	public function addProjection($table, $attr, $alias = null)
	{
		// TODO:
		// CHECK CORRECTITUD: proyeccion debe tener aliases presentes en el from. Necesario agregar primero el FROM.

//		$p = new stdClass(); // Objeto anonimo.
//		$p->alias = $alias;
//		$p->attr = $attr;
//
//		$this->select[] = $p;

      $projection = new SelectAttribute($table, $attr);
      if ($alias != null)
      {
         $projection->setAlias($alias);
      }
      $this->select->add( $projection );

		return $this;
	}
   
   public function addAggregation($aggName, $table, $attr, $alias = null)
   {
      $selAttr = new SelectAttribute($table, $attr);
      $aggregation= new SelectAggregation( $aggName, $selAttr );
      
      if (!is_null($alias))
      {
         $aggregation->setAlias($alias);
      }
      
      $this->select->add( $aggregation );
      
      return $this;
   }
   
   public function getSelect()
   {
      return $this->select;
   }
   public function getFrom()
   {
      return $this->from;
   }
   public function getWhere()
   {
      return $this->where;
   }
   public function getOrder()
   {
      return $this->order;
   }

   // FIXME: el from deberia calcularse solo en base a la clase de dominio sobre
   //        la cual se esta buscando o si la condicion se especifica sobre
   //        atributos de clases distintas, usar esas clases.
   
   /* Como se usa hoy: hay que hallar tableName
   $q = new Query();
      $q->addFrom( $tableName, "ref" )
          ->addProjection( "ref", "id" )
            ->setCondition(
               Condition::_AND()
                 ->add( Condition::EQ("ref", "owner_id", $owner->getId()) )
                 ->add( Condition::EQ("ref", "ref_id", $child->getId()) )
              );
   */
   
   /**
    * Agrega una tabla fuente de los datos que devuelve la consulta.
    */
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
   /**
    * Agrega una condicion a la consulta.
    */
	public function setCondition(Condition $cond)
	{
		$this->where = $cond;
      return $this;
	}

   /**
    * Agrega un limite de regsitros que devuelve la consulta.
    */
	public function setLimit($max, $offset)
	{
		$this->limit_max = $max;
		$this->limit_offset = $offset;
      return $this;
	}
   public function hasLimit()
   {
      return (!empty($this->limit_max));
   }
   public function getLimitMax()
   {
      return $this->limit_max;
   }
   
   public function getLimitOffset()
   {
      return $this->limit_offset;
   }
   public function setLimitTable($table)
   {
      $this->limit_table = $table;
      return $this;
   }
   public function getLimitTable()
   {
      return $this->limit_table;
   }
   
   /**
    * Agrega ordenamiento por alguna columna de una tabla.
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
   
   /**
    * Genera
    *   GROUP BY table.attr, table.attr, ...
    *   GROUP BY funct(table.attr), ...
    */
   public function addGroupBy($table, $attr, $funct = null)
   {
      $groupBy = new stdClass(); // Objeto anonimo.
      $groupBy->attr = $attr;
      $groupBy->table = $table;
      $groupBy->funct = $funct;
      
      $this->groupBy[] = $groupBy;
      
      return $this;
   }
   
   public function getGroupBy()
   {
      return $this->groupBy;
   }
}
?>