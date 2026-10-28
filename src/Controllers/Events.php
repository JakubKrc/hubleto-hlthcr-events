<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Controllers;

class Events extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEvents/Events.twig');
  }
}
