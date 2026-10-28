<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Hubleto\App\External\WaiBlue\HlthcrEvents\Services\EventAccess;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHasUser extends \Hubleto\Erp\RecordManager
{
  public $table = 'hlthcr_event_has_users';

  public function EVENT(): BelongsTo
  {
    return $this->belongsTo(Event::class, 'id_event', 'id');
  }

  public function USER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_user', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $this->restrictToVisibleRows($query);
  }

  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);
    return $this->restrictToVisibleRows($query);
  }

  // Administrators and managers see all attendees, other users only their own attendance.
  private function restrictToVisibleRows(mixed $query): mixed
  {
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    /** @var EventAccess */
    $eventAccess = $hubleto->getService(EventAccess::class);
    if ($eventAccess->canManageEvents()) return $query;

    return $query->where($this->table . '.id_user', $hubleto->authProvider()->getUserId());
  }

  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    return parent::addUrlFiltersToQuery($query);
  }

  public function prepareLookupData(array $dataRaw): array
  {
    return parent::prepareLookupData($dataRaw);
  }
}
