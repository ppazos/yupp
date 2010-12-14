<?php

YuppLoader::load('tests.model.i006', 'Recipiente1');

/**
 * Clase modelo para el test I006.
 */
class Vaso1 extends Recipiente1
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_i006_vaso1");

      $this->addAttribute("marca",  Datatypes :: TEXT);
      
      // TODO: seria bueno probar una Constraint de que el volumen
      // del contenido no pueda ser mayor que la capacidad del recipiente.
      $this->addHasOne("contenido", "Contenido1");
      
      
      parent :: __construct($args, $isSimpleInstance);
      
      
      // WARNING:
      // para definir una restriccion sobre un atributo de la superclase,
      // debe inicializarse la superclase primero.
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
      
      /*
      $this->constraints = array (
         "material" => array (
            Constraint :: maxLength(30),
            Constraint :: blank(false),
            Constraint :: inList( array("vidrio","plastico") ) // TODO: probar si se verifican estas restricciones para Vaso o si se verifican las de Recipiente...
         ),
         "marca" => array (
            Constraint :: nullable(true)
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
} // Model006
?>