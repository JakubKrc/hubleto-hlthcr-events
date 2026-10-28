<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents;

use Hubleto\App\External\WaiBlue\HlthcrCore\Models\Car;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\DemoData;

class Loader extends \Hubleto\App\External\WaiBlue\HlthcrCore\HlthcrApp
{
  const CAR_BOOKINGS_URL = 'hlthcr-events/events?filters['
    . Models\RecordManagers\Event::TYPE_FILTER . ']='
    . Models\RecordManagers\Event::TYPE_FILTER_CAR_BOOKINGS;

  public function init(): void
  {
    \Hubleto\App\External\WaiBlue\HlthcrCore\Services\Translator::register();
    parent::init();

    $this->router()->get([
      '/^hlthcr-events\/?$/' => Controllers\Home::class,
      '/^hlthcr-events\/settings\/?$/' => Controllers\Settings::class,
    ]);
    $this->router()->crud('hlthcr-events/events', Controllers\Events::class);

    /** @var \Hubleto\App\Community\Calendar\Manager $calendarManager */
    $calendarManager = $this->getService(\Hubleto\App\Community\Calendar\Manager::class);
    $calendarManager->addCalendar($this, 'hlthcr-events', Calendar::class);
    $calendarManager->addCalendar($this, 'hlthcr-events-cars', CarsCalendar::class);
  }

  public function installApp(int $round): void
  {
    \Hubleto\App\External\WaiBlue\HlthcrCore\Services\Translator::register();

    if ($round == 1) {
      $this->getModel(Models\Event::class)->upgradeSchema();
      // "php hubleto app install" runs only round 1, so foreign keys are created here too (Hubleto skips them later).
      $this->installForeignKeys();
    }
  }

  public function search(array $expressions): array
  {
    $eventsQuery = $this->getModel(Models\Event::class)->record->prepareReadQuery();
    foreach ($expressions as $expression) {
      $eventsQuery = $eventsQuery->where('hlthcr_events.name', 'like', '%' . $expression . '%');
    }

    $results = [];
    foreach ($eventsQuery->orderBy('date_start', 'desc')->limit(20)->get()->toArray() as $event) {
      $results[] = [
        'id' => $event['id'],
        'label' => $event['name'],
        'url' => 'hlthcr-events/events/' . $event['id'],
        'description' => date('d.m.Y', strtotime($event['date_start'])),
      ];
    }
    return $results;
  }

  public function renderSecondSidebar(): string
  {
    return '
      ' . $this->secondSidebarTitle() . '
      <div class="app-sidebar-buttons">
        ' . $this->secondSidebarButton('hlthcr-events/events', 'fas fa-calendar-check', $this->translate('Events')) . '
        ' . $this->secondSidebarButton(self::CAR_BOOKINGS_URL, 'fas fa-car', $this->translate('Car bookings')) . '
        ' . $this->secondSidebarButton('calendar', 'fas fa-calendar-days', $this->translate('Calendar')) . '
        ' . $this->secondSidebarButton('hlthcr-events/settings', 'fas fa-cog', $this->translate('Settings')) . '
      </div>
    ';
  }

  public function renderAlerts(): string
  {
    return '';
  }

  public function getSidebarBadgeNumber(): int
  {
    return 0;
  }

  /**
   * Demo events and car bookings in the current week (FAKE data).
   * Records are inserted directly: the model rules use the logged-in user, and there is none on the command line.
   */
  public function generateDemoData(): void
  {
    /** @var Models\Event */
    $mEvent = $this->getModel(Models\Event::class);
    $demoEventsExist = $mEvent->record->where('name', 'Porada tímu')->exists();
    if ($demoEventsExist) return;

    $demoUserIds = $this->getService(DemoData::class)->ensureDemoUsers();
    $cars = $this->getModel(Car::class)->record->orderBy('id')->get();
    $monday = date('Y-m-d', strtotime('monday this week'));
    $tuesday = date('Y-m-d', strtotime($monday . ' +1 day'));
    $wednesday = date('Y-m-d', strtotime($monday . ' +2 days'));

    $demoEvents = [
      [
        'name' => 'Porada tímu',
        'date_start' => $monday,
        'time_start' => '09:00:00',
        'date_end' => $monday,
        'time_end' => '10:00:00',
        'id_user' => $demoUserIds['manager'],
        'is_public' => 1,
        'note' => 'Zasadačka',
      ],
      [
        'name' => 'Obhliadka prevádzky',
        'date_start' => $tuesday,
        'time_start' => '08:00:00',
        'date_end' => $tuesday,
        'time_end' => '12:00:00',
        'id_user' => $demoUserIds['hygienist-1'],
        'attendee_user_ids' => json_encode([$demoUserIds['nurse']]),
        'note' => 'Demo Stavby s.r.o.',
      ],
      [
        'name' => 'Školenie prvej pomoci',
        'date_start' => $wednesday,
        'date_end' => $wednesday,
        'is_all_day' => 1,
        'id_user' => $demoUserIds['hygienist-2'],
      ],
    ];

    if (count($cars) > 0) {
      $demoEvents[] = [
        'name' => 'Služobná cesta Trnava',
        'date_start' => $tuesday,
        'time_start' => '07:00:00',
        'date_end' => $tuesday,
        'time_end' => '13:00:00',
        'id_user' => $demoUserIds['hygienist-1'],
        'id_car' => $cars[0]->id,
        'note' => 'Trnava',
      ];
    }

    foreach ($demoEvents as $demoEvent) {
      $mEvent->record->create($demoEvent);
    }
  }
}
