<pre>
<?php

//include_once("core.db.criteria2.Select.class.php");

include_once("core.db.criteria2.Condition.class.php");
include_once("core.db.criteria2.ComplexCondition.class.php");
include_once("core.db.criteria2.CompareCondition.class.php");
include_once("core.db.criteria2.BinaryInfixCondition.class.php");
include_once("core.db.criteria2.UnaryPrefixCondition.class.php");
include_once("core.db.criteria2.Query.class.php");

$q = new Query();

$q->addFrom('Person',   'per');
$q->addFrom('Employee', 'emp');

echo $q->evaluate();

echo "<hr/>";

$c = Condition::_AND()
          ->add( Condition::EQ("per", "nombre", "Carlox") )
          ->add( Condition::GT("per", "edad", 5) )
          ->add( Condition::EQA("per", "nombre", "per", "nombre2") );

echo $c->evaluate();

//echo Condition::EQ("per", "nombre", "Carlox")->evaluate();
//echo Condition::EQA("per", "nombre", "per", "nombre2")->evaluate();

/*
$sel = new Select();

$sel->addProjection("per", "name");
$sel->addProjection("per", "age");
$sel->addAvg("per", "age");
echo $sel->evaluate();
*/


?>
</pre>