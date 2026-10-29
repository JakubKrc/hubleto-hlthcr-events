<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Controllers;

class EventHasUsers extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEvents/EventHasUsers.twig');
  }
}
