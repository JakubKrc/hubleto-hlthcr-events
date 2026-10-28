<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents;

use Hubleto\App\External\WaiBlue\HlthcrEvents\Models\Event;

/**
 * Shared part of the events calendar and the cars calendar: loads visible events and converts them.
 */
abstract class BaseCalendar extends \Hubleto\App\Community\Calendar\Calendar
{
  // Legacy calendar: executed events are grey.
  const EXECUTED_COLOR = '#8e9091';

  public string $translationContext = 'hubleto-app-external-waiblue-hlthcrevents-loader';
  public string $translationContextInner = 'Calendar';

  public function loadEvent(int $id): array
  {
    $event = $this->getModel(Event::class)->record->prepareReadQuery()->where('hlthcr_events.id', $id)->first();
    if (!$event) return [];
    return $event->toArray();
  }

  /**
   * Visible events overlapping the given period. $filter comes from the calendar ("my events", user).
   */
  protected function loadEventsInPeriod(string $dateStart, string $dateEnd, array $filter, bool $carBookings): array
  {
    /** @var Event */
    $mEvent = $this->getModel(Event::class);
    $query = $mEvent->record->prepareReadQuery()
      ->where('hlthcr_events.date_start', '<=', $dateEnd)
      ->where('hlthcr_events.date_end', '>=', $dateStart)
    ;

    if ($carBookings) {
      $query = $query->whereNotNull('hlthcr_events.id_car');
    } else {
      $query = $query->whereNull('hlthcr_events.id_car');
    }

    $idSelectedUser = (int) ($filter['idUser'] ?? 0);
    $showsOnlyMyEvents = (int) ($filter['fOwnership'] ?? 0) == 1;
    if ($showsOnlyMyEvents) $idSelectedUser = $this->authProvider()->getUserId();
    if ($idSelectedUser > 0) {
      $query = $mEvent->record->filterByUser($query, $idSelectedUser);
    }

    return $query->get()->toArray();
  }

  protected function convertToCalendarEvent(array $event, string $source, string $color): array
  {
    $isAllDay = (bool) $event['is_all_day'];
    $start = $event['date_start'];
    $end = $event['date_end'];

    if ($isAllDay) {
      // An all-day event ends at the end of its last day (the calendar expects the next day).
      $end = date('Y-m-d', strtotime($event['date_end'] . ' +1 day'));
    } else {
      $timeStart = (string) ($event['time_start'] ?? '');
      $timeEnd = (string) ($event['time_end'] ?? '');
      // Without an end time the event lasts until the end of its last day, as the event form checks it.
      $start .= ' ' . ($timeStart == '' ? '00:00:00' : $timeStart);
      $end .= ' ' . ($timeEnd == '' ? '23:59:59' : $timeEnd);
    }

    return [
      'id' => (int) $event['id'],
      'start' => $start,
      'end' => $end,
      'allDay' => $isAllDay,
      'title' => $event['name'],
      'color' => (bool) $event['is_executed'] ? self::EXECUTED_COLOR : $color,
      'source' => $source,
      'id_owner' => $event['id_user'],
      'url' => 'hlthcr-events/events/' . $event['id'],
    ];
  }
}
