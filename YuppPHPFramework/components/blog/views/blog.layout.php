<html>
  <head>
    <style type="text/css">
      ul.postnav, ul.postnav li {
         margin: 0px;
         padding: 0px;
         list-style-type:none;
      }
      ul.postnav li { 
         float:left;
         margin-right: 5px;
         *width: 150px;
      }
      ul.postnav a {
         display:block;
         padding: 5px 10px 5px 10px;
         background: #C7FF5A;
         color: #666;
         text-decoration:none;
         text-align:center;
      }
      ul.postnav a:hover { background: #A8E52F; color:#FFF; }
      
      
      
      ul.postnav div {
         display:block;
         /* padding: 5px 10px 5px 10px; */
         /* background: #C7FF5A; */
         color: #666;
         padding-top: 4px;
      }
    </style>
    <?php echo h("js",  array("name" => "prototype-1.6.0.2") ); ?>
    <?php echo $head; ?>
  </head>
  <body>
    <div style="padding:10px; background-color:#6af;" align="right">
      <?php echo h('locale_chooser'); ?>
      <?php if ($u = YuppSession::get("user") !== NULL) : ?>
        <b>
           <?php echo Helpers::link( array("controller" => "usuario",
                                           "action"     => "logout",
                                           "body"       => DisplayHelper::message("blog.usuario.action.logout")) ); ?>
        </b>
        |
        <?php echo Helpers::link( array("controller" => "usuario",
                                        "action"     => "list",
                                        "body"       => DisplayHelper::message("blog.usuario.action.list")) ); ?>
        |
        <?php echo Helpers::link( array("controller" => "entradaBlog",
                                        "action"     => "list",
                                        "body"       => DisplayHelper::message("blog.entrada.action.list")) ); ?>
        
      <?php endif; ?>
      
    </div>
    <div style="padding:10px;"><?php echo $body; ?></div>
  </body>
</html>