<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\RecordManagers;

use Hubleto\App\External\WaiBlue\HlthcrCore\Models\RecordManagers\Traits\DefaultOrder;
use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Hubleto\App\External\WaiBlue\HlthcrCore\Models\RecordManagers\Car;
use Hubleto\App\External\WaiBlue\HlthcrEvents\Services\EventAccess;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends \Hubleto\Erp\RecordManager
{
  use DefaultOrder;

  public $table = 'hlthcr_events';

  const TYPE_FILTER = 'fEventType';
  const TYPE_FILTER_ALL = 0;
  const TYPE_FILTER_EVENTS = 1;
  const TYPE_FILTER_CAR_BOOKINGS = 2;

  public function USER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_user', 'id');
  }

  public function CAR(): BelongsTo
  {
    return $this->belongsTo(Car::class, 'id_car', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $this->restrictToVisibleEvents($query);
  }

  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);
    return $this->restrictToVisibleEvents($query);
  }

  /**
   * Legacy: administrators and managers see all events. Others see car bookings, own events, events they attend
   * and events "for all" (except medical staff).
   */
  private function restrictToVisibleEvents(mixed $query): mixed
  {
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    /** @var EventAccess */
    $eventAccess = $hubleto->getService(EventAccess::class);
    if ($eventAccess->canManageEvents()) return $query;

    $idUser = $hubleto->authProvider()->getUserId();
    $seesPublicEvents = $eventAccess->seesPublicEvents();

    return $query->where(function ($visibleEvents) use ($idUser, $seesPublicEvents) {
      $visibleEvents->whereNotNull($this->table . '.id_car');
      $visibleEvents->orWhere($this->table . '.id_user', $idUser);
      $visibleEvents->orWhereIn($this->table . '.id', $this->idsOfEventsAttendedBy($idUser));
      if ($seesPublicEvents) {
        $visibleEvents->orWhere($this->table . '.is_public', 1);
      }
    });
  }

  // Events of one user: own events, events the user attends and events "for all" (both legacy calendars).
  public function filterByUser(mixed $query, int $idUser): mixed
  {
    return $query->where(function ($eventsOfUser) use ($idUser) {
      $eventsOfUser->where($this->table . '.id_user', $idUser);
      $eventsOfUser->orWhereIn($this->table . '.id', $this->idsOfEventsAttendedBy($idUser));
      $eventsOfUser->orWhere($this->table . '.is_public', 1);
    });
  }

  private function idsOfEventsAttendedBy(int $idUser): \Closure
  {
    return function ($attendedEventIds) use ($idUser) {
      $attendedEventIds->select('id_event')->from('hlthcr_event_has_users')->where('id_user', $idUser);
    };
  }

  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    $query = parent::addUrlFiltersToQuery($query);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    $router = $hubleto->router();

    // Car form tab "Plánovanie vozidiel".
    $idCar = $router->urlParamAsInteger('idCar');
    if ($idCar > 0) {
      $query = $query->where($this->table . '.id_car', $idCar);
    }

    $filters = $router->urlParamAsArray('filters');
    $type = (int) ($filters[self::TYPE_FILTER] ?? self::TYPE_FILTER_ALL);
    if ($type == self::TYPE_FILTER_EVENTS) {
      $query = $query->whereNull($this->table . '.id_car');
    }
    if ($type == self::TYPE_FILTER_CAR_BOOKINGS) {
      $query = $query->whereNotNull($this->table . '.id_car');
    }

    return $query;
  }

  public function prepareLookupData(array $dataRaw): array
  {
    return parent::prepareLookupData($dataRaw);
  }
}
