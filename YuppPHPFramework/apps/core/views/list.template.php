<?php
/*
 * $class: clase de los objetos a mostrar
 * $list: lista con objetos a mostrar
 * Codigo copiado de DisplayHelper.display_list
 */

$ins = new $class(); // Instancia para saber nombres de columnas...
$attrs = $ins->getAttributeTypes();
$hmattrs = $ins->getHasMany();
$ctx = YuppContext::getInstance();
$m = Model::getInstance();
$app = $m->get('app'); // Cuando se genera por la ap "core", viene "app" como parametro.
if ( !isset($app) ) $app = $ctx->getApp(); // Cuando se genera por una app que no es "core"

YuppLoader::load('core.app', 'App');
$theApp = new App($app);

?>
<script type="text/javascript">
var td = null;
function callback(data, textStatus, jqXHR)
{
   //$('#content').html(data);
   td.html(data);
   // textStatus: success
   // jqXHR: XMLHttpRequest
   // alert(jqXHR.getAllResponseHeaders());
}
function before (tdid)
{
   td = $('#'+tdid);
}
</script>

<div id="content"></div>

<table>
  <tr><!-- Cabezal -->
    <?php foreach ($attrs as $attr => $type ) : ?>
      <?php if ( $attr === 'deleted' || DatabaseNormalization::isSimpleAssocName($attr)) continue; // No quiero mostrar la columna 'deleted' o hasone attr_id ?>
      <th>
        <?php echo h('orderBy', array('attr'=>$attr, 'action'=>$ctx->getAction(), 'body'=>$attr, 'params'=>array('app'=>$app,'class'=>$m->get('class')))); ?>
      </th>
    <?php endforeach; ?>
    <?php foreach ($hmattrs as $attr => $class ) : ?>
      <th>
        <?php echo $attr; ?>
      </th>
    <?php endforeach; ?>
  </tr>
  <?php foreach ($list as $po) : ?>
    <tr>
      <?php foreach ( $attrs as $attr => $type ) : ?>
        <?php if ( $attr === 'deleted' || DatabaseNormalization::isSimpleAssocName($attr)) continue; // No quiero mostrar la columna 'deleted' o hasone attr_id ?>
        <td>
          <?php if ($attr == "id") : ?>
            <?php
              // Si en la aplicacion actual existe el controlador para esta clase de dominio, 
              // que vaya a la aplicacion actual y a ese controller.
              // Si no, va a la app y controller "core".
              if ($theApp->hasController($po->aGet('class'))) : ?>
                <?php echo h('link',
                             array('app'    => $app,
                                   'controller' => String::firstToLower( $po->aGet('class') ),
                                   'action' => 'show',
                                   'class'  => $po->aGet('class'),
                                   'id'     => $po->aGet($attr),
                                   'params' => array('app'=>$app),
                                   'body'   => $po->aGet($attr) )); ?>
             <?php else : ?>
               <?php echo h('link',
                            array('app'    => 'core',
                                  'controller' => 'core',
                                  'action' => 'show',
                                  'class'  => $po->aGet('class'),
                                  'id'     => $po->aGet($attr),
                                  'params' => array('app'=>$app),
                                  'body'   => $po->aGet($attr) )); ?>
            <?php endif; ?>
          <?php else : ?>
            <?php echo $po->aGet($attr); ?>
          <?php endif; ?>
        </td>
      <?php endforeach; ?>
      
      <?php foreach ($hmattrs as $attr => $class ) : ?>
      <td id="<?php echo $po->getId().'_'.$attr; ?>">
        <?php echo h('ajax_link', 
                     array('app'=>'core', 'controller'=>'core', 'action'=>'listMany',
                           'params'=>array('class'=>$po->aGet('class'), 'id'=>$po->getId(),'refclass'=>$class, 'attr'=>$attr, 'app'=>$app),
                           'after'=>'callback', 'before'=>'before("'. $po->getId().'_'.$attr. '")', 'body'=>'ver')); ?>
      </td>
    <?php endforeach; ?>
      
    </tr>
  <?php endforeach; ?>
</table>