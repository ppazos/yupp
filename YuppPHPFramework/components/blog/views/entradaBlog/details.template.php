<script type="text/javascript">
  var before_<?php echo $entrada->getId(); ?> = function(req, json) {
    
    $('ajax_comments_<?php echo $entrada->getId(); ?>').style.display = "block";
    $('ajax_comments_<?php echo $entrada->getId(); ?>').innerHTML = "Cargando...";
  }
  var after_<?php echo $entrada->getId(); ?> = function(req, json) {
   
    if (!json) json = req.responseText.evalJSON();
    else       json = json.evalJSON();
     
    var res = "";
    for (var i = 0; i < json.comentarios.length; i++) {
      com = json.comentarios[i].attributes;
      res += '<b>'+com.fecha+'</b><br/>'+com.texto;
      if (i+1<json.comentarios.length) res += '<br/><br/>';
    }
     
    $('ajax_comments_<?php echo $entrada->getId(); ?>').innerHTML = res;
  }
</script>

<div class="entrada">
  <div class="top">
    <div class="left">
      (<?php echo $entrada->getId(); ?>)
      <?php echo Helpers::link( array("controller" => "entradaBlog",
                                      "action" => "show",
                                      "id" => $entrada->getId(),
                                      "body" => $entrada->getTitulo()) ); ?>
    </div>
    <div class="right">
      <?php echo $entrada->getFecha(); ?>
    </div>
  </div>
  <br/>
  <div class="content">
    <?php echo $entrada->getTexto(); ?>
  </div>
  <div class="bottom">
    <div class="left">
      <?php echo Helpers::link( array("controller" => "comentario",
                                      "action" => "create",
                                      "id" => $entrada->getId(),
                                      "body" => DisplayHelper::message("blog.entrada.action.addComment")) ); ?>
                                      

    </div>
    <div class="right">
      <?php echo DisplayHelper::message("blog.entrada.label.comments"); ?>:
      <?php echo Helpers::ajax_link(  array(
                                            "action"  => "getCommentsJSON",
                                            "id"      => $entrada->getId(),
                                            "body"    => "(".count($entrada->getComentarios()).")",
                                            "after"   => "after_".$entrada->getId(),
                                            "before"  => "before_".$entrada->getId() )  ); ?>
      <?php /* echo count( $entrada->getComentarios() ); */ ?>
    </div>
  </div>
  <div style="padding-top:20px; padding-left:10px; padding-right:10px; display:none;" id="ajax_comments_<?php echo $entrada->getId(); ?>"></div>
  
</div>