<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Controllers;

class Settings extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      ['url' => 'hlthcr-events', 'content' => $this->translate('Events')],
      ['url' => 'settings', 'content' => $this->translate('Settings')],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEvents/Settings.twig');
  }
}
