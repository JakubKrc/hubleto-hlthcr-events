<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\HlthcrCore\Models\Car;
use Hubleto\App\External\WaiBlue\HlthcrEvents\Services\EventAccess;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Time;
use Hubleto\Framework\Db\Column\Varchar;

/**
 * Calendar event (legacy "Udalosť"). With a car it is a car booking (legacy "Naplánovať vozidlo").
 * Events of audits, documents and exams are not stored here; their apps derive them from their records.
 * Date and time are separate columns: Hubleto's date-time input drops the time (dev-main).
 */
class Event extends \Hubleto\App\External\WaiBlue\HlthcrCore\Models\HlthcrModel
{
  public string $table = 'hlthcr_events';
  public string $recordManagerClass = RecordManagers\Event::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlDetail = 'hlthcr-events/events/{%ID%}';

  public array $relations = [
    'USER' => [ self::BELONGS_TO, User::class, 'id_user', 'id' ],
    'CAR' => [ self::BELONGS_TO, Car::class, 'id_car', 'id' ],
  ];

  public function describeColumns(): array
  {
    $canManageEvents = $this->eventAccess()->canManageEvents();

    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Event name')))->setRequired()->setDefaultVisible(),
      'date_start' => (new Date($this, $this->translate('Start date')))->setRequired()->setDefaultVisible(),
      'time_start' => (new Time($this, $this->translate('Start time')))->setDefaultVisible(),
      'date_end' => (new Date($this, $this->translate('End date')))->setRequired()->setDefaultVisible(),
      'time_end' => (new Time($this, $this->translate('End time')))->setDefaultVisible(),
      'is_all_day' => (new Boolean($this, $this->translate('All-day event')))
        ->setDefaultValue(false)
        ->setHint($this->translate('Only the dates are used. An event with a time is never all-day.'))
      ,
      'id_user' => (new Lookup($this, $this->translate('User'), User::class))
        ->setRequired()
        ->setDefaultVisible()
        ->setReactComponent('InputUserSelect')
        ->setReadonly(!$canManageEvents)
      ,
      'id_car' => (new Lookup($this, $this->translate('Car'), Car::class, 'RESTRICT'))->setDefaultVisible(),
      'is_executed' => (new Boolean($this, $this->translate('Executed')))->setDefaultValue(false)->setDefaultVisible(),
      'note' => (new Varchar($this, $this->translate('Workplace (note)')))->setDefaultVisible(),
      'is_public' => (new Boolean($this, $this->translate('For all')))
        ->setDefaultValue(false)
        ->setDefaultVisible()
        ->setReadonly(!$canManageEvents)
      ,
      // JSON list of user ids, edited with checkboxes (legacy table events_attendance).
      'attendee_user_ids' => (new Json($this, $this->translate('Attendees')))->setReadonly(!$canManageEvents),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();

    // In the car form tab the button books the car.
    $idCar = $this->router()->urlParamAsInteger('idCar');
    $description->ui['addButtonText'] = $idCar > 0 ? $this->translate('Book the car') : $this->translate('Add event');
    $description->ui['orderBy'] = ['field' => 'date_start', 'direction' => 'asc'];
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);

    $description->addFilter(RecordManagers\Event::TYPE_FILTER, [
      'title' => $this->translate('Type'),
      'options' => [
        RecordManagers\Event::TYPE_FILTER_ALL => $this->translate('All'),
        RecordManagers\Event::TYPE_FILTER_EVENTS => $this->translate('Events'),
        RecordManagers\Event::TYPE_FILTER_CAR_BOOKINGS => $this->translate('Car bookings'),
      ],
    ]);

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->defaultValues['id_user'] = $this->authProvider()->getUserId();
    $description->defaultValues['attendee_user_ids'] = '[]';
    return $description;
  }

  public function getRelationsIncludedInLoadTableData(): array|null
  {
    return ['USER', 'CAR'];
  }

  public function getMaxReadLevelForLoadTableData(): int
  {
    return 1;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);

    if (!$this->eventAccess()->canManageEvents()) {
      $record['id_user'] = $this->authProvider()->getUserId();
      $record['is_public'] = false;
      $record['attendee_user_ids'] = '[]';
    }

    return $this->normalizeEvent($record);
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);

    $originalEvent = $this->record->where('id', (int) $record['id'])->first();
    if ($originalEvent && !$this->eventAccess()->canManageEvents()) {
      if ((bool) $originalEvent->is_public) {
        throw new \Exception($this->translate('Only administrators and managers can change events for all.'));
      }
      $record['id_user'] = $originalEvent->id_user;
      $record['is_public'] = false;
      $record['attendee_user_ids'] = $originalEvent->attendee_user_ids;
    }

    return $this->normalizeEvent($record);
  }

  public function onBeforeDelete(int $id): int
  {
    $id = parent::onBeforeDelete($id);

    $event = $this->record->where('id', $id)->first();
    $isPublicEvent = $event && (bool) $event->is_public;
    if ($isPublicEvent && !$this->eventAccess()->canManageEvents()) {
      throw new \Exception($this->translate('Only administrators and managers can change events for all.'));
    }

    return $id;
  }

  /**
   * Legacy rules: the end is not before the start, and an event with a time is not all-day.
   */
  private function normalizeEvent(array $record): array
  {
    $timeStart = (string) ($record['time_start'] ?? '');
    $timeEnd = (string) ($record['time_end'] ?? '');
    // An end day without a time lasts until the end of that day.
    $start = strtotime($record['date_start'] . ' ' . ($timeStart == '' ? '00:00:00' : $timeStart));
    $end = strtotime($record['date_end'] . ' ' . ($timeEnd == '' ? '23:59:59' : $timeEnd));

    if ($start !== false && $end !== false && $end < $start) {
      throw new \Exception($this->translate('The event cannot end before it starts.'));
    }

    $hasTime = ($timeStart != '' && $timeStart != '00:00:00') || ($timeEnd != '' && $timeEnd != '00:00:00');
    if ($hasTime) $record['is_all_day'] = false;

    $record['attendee_user_ids'] = json_encode($this->getAttendeeUserIds($record));

    return $record;
  }

  /**
   * Attendees as a list of user ids. The form sends a JSON string; old rows may be empty.
   */
  public function getAttendeeUserIds(array $event): array
  {
    $attendees = $event['attendee_user_ids'] ?? '[]';
    if (is_string($attendees)) $attendees = json_decode($attendees, true);
    if (!is_array($attendees)) return [];

    $attendeeUserIds = [];
    foreach ($attendees as $idUser) {
      if ((int) $idUser > 0) $attendeeUserIds[] = (int) $idUser;
    }
    return array_values(array_unique($attendeeUserIds));
  }

  private function eventAccess(): EventAccess
  {
    return $this->getService(EventAccess::class);
  }
}
