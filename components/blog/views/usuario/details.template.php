<tr>
  <td>
    <?php echo $usuario->getId(); ?>
  </td>
  <td>
    <?php echo $usuario->getNombre(); ?>
  </td>
  <td>
    <?php echo $usuario->getEmail(); ?>
  </td>
  <td>
    <?php echo $usuario->getClave(); ?>
  </td>
    <td>
    <?php echo $usuario->getEdad(); ?>
  </td>
  <td>
    <?php echo h("link", array("action" => "delete",
                               "id"     => $usuario->getId(),
                               "body"   => "[ X ]") ); ?>
  </td>
</tr>