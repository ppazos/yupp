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

<h1>Show</h1>

<div align="center"><?php echo $m->flash('message'); ?></div>

<?php echo DisplayHelper::model( $m->get('object'), "show" ); ?>

<br/><br/>

<?php $clazz = $m->get('object')->aGet('class'); ?>
<?php $id    = $m->get('object')->aGet('id'); ?>

[ <a href="edit?class=<?php echo $clazz ?>&id=<?php echo $id ?>">Edit</a> ]
[ <a href="delete?class=<?php echo $clazz ?>&id=<?php echo $id ?>">Delete</a> ]
[ <a href="list?class=<?php echo $clazz ?>">List</a> ]

</body>
</html>