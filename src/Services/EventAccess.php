<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Services;

use Hubleto\App\External\WaiBlue\HlthcrCore\Services\Roles;

/**
 * Role rules of events, taken over from the legacy calendar.
 */
class EventAccess extends \Hubleto\Erp\Core
{
  private function roles(): Roles
  {
    return $this->getService(Roles::class);
  }

  // Legacy: administrators and managers see all events and set the user, attendees and "for all".
  public function canManageEvents(): bool
  {
    return $this->roles()->isActiveUserAdministratorOrManager();
  }

  // Legacy: events "for all" are shown to every role except medical staff.
  public function seesPublicEvents(): bool
  {
    if ($this->canManageEvents()) return true;
    return !$this->roles()->activeUserHasRole(Roles::MEDICAL_STAFF);
  }
}
