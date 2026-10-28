<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents;

/**
 * Shared events (without a car) in the Hubleto calendar.
 */
class Calendar extends BaseCalendar
{
  const COLOR = '#3788d8';

  public function getCalendarConfig(): array
  {
    return [
      'position' => 8,
      'color' => self::COLOR,
      'title' => $this->translate('Events'),
      'icon' => 'fas fa-calendar-check',
    ];
  }

  public function loadEvents(string $dateStart, string $dateEnd, array $filter = []): array
  {
    $calendarEvents = [];
    foreach ($this->loadEventsInPeriod($dateStart, $dateEnd, $filter, false) as $event) {
      $calendarEvents[] = $this->convertToCalendarEvent($event, 'hlthcr-events', self::COLOR);
    }
    return $calendarEvents;
  }
}
