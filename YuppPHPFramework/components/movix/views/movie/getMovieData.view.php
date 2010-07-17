<?php

$m = Model::getInstance();

YuppLoader::load("core.mvc.form", "YuppForm2");
YuppLoader::load("core.basic", "YuppDateTime");

$movie = $m->get('movie');

?>
<html>
   <head>
      <?php echo h("js",  array("name" => "prototype_161") ); ?>
      <style>
         /* Estilo para YuppForm */
         .field_container {
            width: 540px;
            text-align: left;
         	display: block;
            padding-top: 10px;
         }
         .field_container .label {
            display: inline;
            padding-right: 10px;
            vertical-align: top;
         }
         .field_container .field {
            display: block;
         }
         .field_container .field input {

         }
         .field_container .field input[type=text] {
         	width: 400px;
         }
         .field_container .field input[type=submit] {
            width: 100px;
         }
         .field_container .field textarea {
            width: 540px;
            height: 200px;
         }
      </style>
      
      <script type="text/javascript">

         Event.observe(window, 'load', function() {
            
            //alert( $('year').type );
            //$$("input[name=year]").value = 'ssss';
            
         //});
         //function fdo()
         //{
         
            //new Ajax.Request('http://www.deanclatworthy.com/imdb/?q=<?php echo str_replace(' ','+',$movie->getName()); ?>', {
            
            var url = '<?php echo h('url', array('action'=>'getMovieDataJSON')) ?>';
            
            new Ajax.Request(url, {
               method: 'get',
               encoding: 'ISO-8859-1',
               parameters: {
                  id: '<?php echo $movie->getId(); ?>',
               },
               onLoading: function(res) {
                  $('status').innerHTML = "Cargando...";
               },
               onSuccess: function(res) {
                  
                  /*
                  if (res.responseText.substring(0,3)=='Error')
                  {
                     alert(res.responseText);
                     //$('status').innerHTML = "Ocurri&oacute; un error, intente nuevamente ("+ res.responseText +")";
                     return;
                  }
                  */
                  
                  var json = res.responseJSON;
                  
                  if (!json)
                  {
                     $('status').innerHTML = res.responseText;
                  }
                  else
                  {
                     //alert( res.responseJSON );
                     //alert(json.year);
                     $('status').innerHTML = "";
                     $('year').value = json.year;
                     $('imdbid').value = json.id;
                     $('genres').value = json.genres;
                     $('votes').value = json.votes;
                     $('rating').value = json.rating;
                     $('url').value = json.imdburl;
                  }
               },
               onFailure: function(res) {
                 /*
                 alert("ERROR 1 "+ res.statusText +"\n" +
                       "text: " + res.responseText + "\n" +
                       "json: " + res.responseJSON + "\n" +
                       "xml: " + res.responseXML);
                 */
                 $('status').innerHTML = "Ocurri&oacute; un error, intente nuevamente ("+ res.status +")";
               },
               onException:  function(res) {
                 /*
                 alert("ERROR 2 "+ res.statusText +"\n" +
                       "text: " + res.responseText + "\n" +
                       "json: " + res.responseJSON + "\n" +
                       "xml: " + res.responseXML);
                 */
                 $('status').innerHTML = "Ocurri&oacute; un error, intente nuevamente ("+ res.status +")";
               }
            });
         //}
         
         }); // onload
      
      </script>
   </head>
   <body>
      <h1>Datos de <?php echo $movie->getName(); ?></h1>
      
      <?php echo DisplayHelper::errors( $movie ); ?>

      <div id="status"></div>
      
      <!--
      <a href="javascript:fdo();">Buscar</a>
      -->
      
      <?php
         $f = new YuppForm2( array('component'=>'movix', 'controller'=>'movie', 'action'=>'getMovieData') );
         $f->add( YuppForm2::hidden( array('name'=>'id',   'value'=>$movie->getId()) ) )
           ->add( YuppForm2::text( array('name'=>'imdbid', 'id'=>'imdbid', 'label'=>'IMDB Id') ) )
           ->add( YuppForm2::text( array('name'=>'genres', 'id'=>'genres', 'label'=>'Generos') ) )
           ->add( YuppForm2::text( array('name'=>'rating', 'id'=>'rating', 'label'=>'Rating') ) )
           ->add( YuppForm2::text( array('name'=>'votes',  'id'=>'votes',  'label'=>'Votos') ) )
           ->add( YuppForm2::text( array('name'=>'year',   'id'=>'year',   'label'=>'A&ntilde;o') ) )
           ->add( YuppForm2::text( array('name'=>'url',    'id'=>'url',    'label'=>'URL') ) )
           ->add( YuppForm2::submit( array('name'  =>'doit',     'label'=>'Guardar cambios')) )
           ->add( YuppForm2::submit( array('action'=>'index',    'label'=>'Cancelar')) );
         
         YuppFormDisplay2::displayForm( $f );
      ?>
      
      <!--
      TODO:
      http://www.movieposterdb.com/embedding
      <script type="text/javascript"
        src="http://www.movieposterdb.com/embed.inc.php?movie_id=0333766">
      </script>
      <script type="text/javascript"
        src="http://www.movieposterdb.com/embed.inc.php?movie_title=Garden State[2004]">
      </script>

       -->
      </div>
   </body>
</html>