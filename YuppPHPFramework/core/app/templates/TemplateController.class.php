<?php

class TemplateController extends YuppController {

	public function indexAction()
	{
      return $this->renderString("Bienvenido a su nueva aplicacion!");
	}
}

?>