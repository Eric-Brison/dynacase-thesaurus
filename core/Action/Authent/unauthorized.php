<?php

function unauthorized(&$action) {
  $action->lay->set("msg", "Vous n'�tes pas autoris� � consulter cette ressource.");

  echo $action->lay->gen();

  $action->session->close();

  exit(0);
}

?>