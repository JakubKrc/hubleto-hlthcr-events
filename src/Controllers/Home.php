<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Controllers;

class Home extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->viewParams['carBookingsUrl'] = \Hubleto\App\External\WaiBlue\HlthcrEvents\Loader::CAR_BOOKINGS_URL;
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEvents/Home.twig');
  }
}
