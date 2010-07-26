<pre>
<?php

include_once("core.db.criteria2.Condition.class.php");
include_once("core.db.criteria2.ComplexCondition.class.php");
include_once("core.db.criteria2.CompareCondition.class.php");
include_once("core.db.criteria2.BinaryInfixCondition.class.php");
include_once("core.db.criteria2.UnaryPrefixCondition.class.php");
include_once("core.db.criteria2.Query.class.php");
include_once("core.db.criteria2.Select.class.php");

include_once("../core.db.DatabaseMySQL.class.php");

$q = new Query();
$q->addFrom('Persona',  'p')
  ->addFrom('Empleado', 'e')
  ->setCondition(
      Condition::_AND()
        ->add( Condition::EQ('p', "nombre", "Carlos") )
        ->add( Condition::GT('p', "edad", 5) )
        ->add( Condition::EQA('p', "nombre", 'p', "nombre2") )
    );

$db = new DatabaseMySQL();
echo $db->evaluateQuery( $q );

//echo Condition::EQ("per", "nombre", "Carlox")->evaluate();
//echo Condition::EQA("per", "nombre", "per", "nombre2")->evaluate();

/*
$sel = new Select();

$sel->addProjection("per", "name");
$sel->addProjection("per", "age");
$sel->addAvg("per", "age");
echo $sel->evaluate();
*/


$q = new Query();
$q->addAggregation( SelectAggregation::AGTN_DISTINTC, 'datos', 'nombre' )
  ->addFrom('Persona',  'datos')
  ->setCondition(
      Condition::GT('p', "edad", 5)
    );

echo $db->evaluateQuery( $q );

?>
</pre>