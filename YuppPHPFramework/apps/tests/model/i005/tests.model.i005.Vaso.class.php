<?php

/**
 * Clase modelo para el test I005.
 */

YuppLoader::load('tests.model.i005', 'Recipiente');

class Vaso extends Recipiente
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_i005_vaso");

      $this->addAttribute("marca",  Datatypes :: TEXT);
      
      // TODO: seria bueno probar una Constraint de que el volumen
      // del contenido no pueda ser mayor que la capacidad del recipiente.
      $this->addHasOne("contenido", "Contenido");
      
      
      parent :: __construct($args, $isSimpleInstance);
      
      // WARNING:
      // para definir una restriccion sobre un atributo de la superclase,
      // debe inicializarse la superclase primero.
      
      //FIXME: no ejecuta estas restricciones porque luego Recipiente las sobreescribe...
      // pasa lo mismo que con el nombre de la tabla, hay que usar un metodo.
      $this->addConstraints(
         "material",
         array (
            Constraint :: maxLength(30),
            Constraint :: blank(false),
            Constraint :: inList( array("vidrio","plastico") ) // TODO: probar si se verifican estas restricciones para Vaso o si se verifican las de Recipiente...
         )
      );
      $this->addConstraints(
         "marca",
         array (
            Constraint :: nullable(true)
         )
      );
      $this->addConstraints(
         "contenido",
         array(
            new VerificarContenido($this) // declarada abajo
         )
      );
      
      
      /*
      $this->constraints = array (
         "material" => array (
            Constraint :: maxLength(30),
            Constraint :: blank(false),
            Constraint :: inList( array("vidrio","plastico") ) // TODO: probar si se verifican estas restricciones para Vaso o si se verifican las de Recipiente...
         ),
         "marca" => array (
            Constraint :: nullable(true)
         ),
         "contenido" => array(
            new VerificarContenido($this) // declarada abajo
         )
      );
      */
      
      // WARNING: atributos del padre se inicializan luego de inicializar el padre!
      // Supongo que estos vasos no tienen tapa
      $this->setTieneTapa(false);
   }
   
   public static function listAll( ArrayObject $params )
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: listAll($params);
   }
   public static function count()
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: count();
   }
   public static function get($id)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: get($id);
   }
   public static function findBy(Condition $condition, ArrayObject $params)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: findBy($condition, $params);
   }
   public static function countBy(Condition $condition)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: countBy($condition);
   }
}

class VerificarContenido extends Constraint
{
   private $vaso;

   public function __construct(Vaso $v)
   {
      $this->vaso = $v;
   }

   public function evaluate($value)
   {
      // El value que me tira PO al evaluar la constraint es la del campo de la constraint, o sea "contenido".
      
      // Que la capacidad del vaso sea mayor que el volumen del contenido
      echo "Capacidad: " . $this->vaso->getCapacidad() . "<br/>";
      echo "Volumen: " . $this->vaso->getContenido()->getVolumen() . "<br/>";
      return ($this->vaso->getCapacidad() >= $this->vaso->getContenido()->getVolumen());
   }

   public function getValue()
   {
      return $this->vaso->getCapacidad(); //$this->vaso; // FIXME: Si retorno un objeto tira error en PO porque trata de generar el mensaje de error a partir de un Obj y no de un valor simple. Esto es una cosa para arreglar!
   }

   public function __toString()
   {
      return "VerificarContenido: [Vaso: " . $this->vaso->getId() . "]";
   }

}



?>