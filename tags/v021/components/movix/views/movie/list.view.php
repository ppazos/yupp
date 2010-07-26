<?php

$m = Model::getInstance();

?>
<html>
  <head>
    <?php echo h("js",  array("name" => "prototype_161") ); ?>
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
      
      .rating {
         height: 12px;
         width: 100px;
         background-color: #66ff66;
         font-size: 10px;
         font-family: tahoma;
         text-align: center;
      }
      .bajo1 {
         width: 15px;
         background-color: #ff3300;
      }
      .bajo2 {
         width: 30px;
         background-color: #ff9999;
      }
      .medio1 {
         width: 45px;
         background-color: #ffdd33;
      }
      .medio2 {
         width: 60px;
         background-color: #ffff66;
      }
      .alto1 {
         width: 75px;
         background-color: #bbee66;
      }
      form {
         display: inline;
      }
    </style>
    <script type="text/javascript">

      Event.observe(window, 'load', function() {
         
        $$('.rating').each( function(value, index) {
          
          //alert( value.innerHTML ); // rating
          
          if ( value.innerHTML )
          {
            var rat = value.innerHTML;
            if (rat <= 1.5) value.addClassName('bajo1');
            else if (rat <= 3.0) value.addClassName('bajo2');
            else if (rat <= 4.5) value.addClassName('medio1');
            else if (rat <= 6.0) value.addClassName('medio2');
            else if (rat <= 7.5) value.addClassName('alto1');
          }
          else
            value.setStyle({display:'none'});
        });
      });
    </script>
  </head>
  <body>

<h1>List</h1>

<div align="center"><?php echo $m->flash('message'); ?></div>

[ <a href="create?class=<?php echo $m->get('class') ?>">Crear</a> ]

<form action="<?php echo h('url', array('action'=>'list')); ?>">
  Filtro:
  <?php $generos = simplexml_load_file('components/movix/config/generos.xml'); ?>
  <select name="filter_genre">
    <option value=""></option>
    <?php foreach ($generos->genero as $genero) : ?>
      <option value="<?php echo $genero; ?>" <?php  echo (($m->get('filter_genre')==$genero)?'selected="true"':''); ?>><?php echo $genero; ?></option>
    <?php endforeach; ?>
  </select>
  <input type="submit" name="doit" value="filtrar" />
</form>
<br/><br/>

<table>
  <tr>
    <th><?php echo h('orderBy', array('attr'=>'name', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'imdbid', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'genres', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'year', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'rating', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'votes', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'url', 'action'=>'list') ); ?></th>
    <th><?php echo h('orderBy', array('attr'=>'id', 'action'=>'list') ); ?></th>
  </tr>
  <?php foreach( $m->get('list') as $movie) : ?>
    <tr>
      <td><?php echo $movie->getName(); ?></td>
      <td><?php echo $movie->getImdbid(); ?></td>
      <td><?php echo $movie->getGenres(); ?></td>
      <td><?php echo $movie->getYear(); ?></td>
      <td><div class="rating"><?php echo $movie->getRating(); ?></div></td>
      <td><?php echo $movie->getVotes(); ?></td>
      <td><a href="<?php echo $movie->getUrl(); ?>">imdb</a></td>
      <td>
        <a href="<?php echo h('url', array('action'=>'show','id'=>$movie->getId())); ?>"><?php echo $movie->getId(); ?></a>
        <br/>
        <a href="<?php echo h('url', array('action'=>'getMovieData','id'=>$movie->getId())); ?>">Obtener datos</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

</br></br>

<?php echo h('pager', array('count'=>$m->get('count'), 'max'=>$m->get('max'), 'offset'=>$m->get('offset'))); ?>

</body>
</html>