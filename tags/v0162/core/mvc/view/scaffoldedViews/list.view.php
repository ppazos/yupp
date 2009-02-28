<?php
/*
 * Created on 23/03/2008
 * lapagina.view.php
 */

$m = Model::getInstance();

?>

<html>
<head>
<style>

table {
   border: 2px solid #000080;
   /* spacing: 0px; */
   border-collapse: separate;
   border-spacing: 0px;
}

th {
   border: 1px solid #000080;
   padding: 5px;
   background-color: #000080;
   color: #fff;
}

td {
   border: 1px solid #69c;
   padding: 5px;
}

</style>
</head>
<body>

<h1>List</h1>

<div align="center"><?php echo $m->flash('message'); ?></div>


[ <a href="create?class=<?php echo $m->get('class') ?>">Create</a> ]<br/><br/>

<?php echo DisplayHelper::model( $m->get('list'), "list", $m->get('class') ); ?>

</br></br>

<?php if ( $m->get('offset')-$m->get('max') >= 0 ) { ?>
[ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')-$m->get('max')); ?>">Previous</a> ]
<?php } ?>

<?php echo (int)($m->get('offset')/$m->get('max') + 1); ?> / <?php echo (int)($m->get('count')/$m->get('max') + 1); ?>

<?php if ( $m->get('offset')+$m->get('max') < $m->get('count') ) { ?>
[ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')+$m->get('max')); ?>">Next</a> ]
<?php } ?>


</body>
</html>