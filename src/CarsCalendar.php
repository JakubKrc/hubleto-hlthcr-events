<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents;

/**
 * Car bookings in the Hubleto calendar (legacy colour purple). Everyone sees them, as in the legacy calendar.
 */
class CarsCalendar extends BaseCalendar
{
  const COLOR = '#9930f0';

  public function getCalendarConfig(): array
  {
    return [
      'position' => 9,
      'color' => self::COLOR,
      'title' => $this->translate('Cars'),
      'icon' => 'fas fa-car',
    ];
  }

  public function loadEvents(string $dateStart, string $dateEnd, array $filter = []): array
  {
    $calendarEvents = [];
    foreach ($this->loadEventsInPeriod($dateStart, $dateEnd, $filter, true) as $event) {
      $calendarEvents[] = $this->convertToCalendarEvent($event, 'hlthcr-events-cars', self::COLOR);
    }
    return $calendarEvents;
  }
}
