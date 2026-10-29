<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\Framework\Db\Column\Lookup;

/**
 * Attendee of an event (legacy table events_attendance). The event form edits the attendees as a list of user ids
 * (Event column attendee_user_ids); only administrators and managers change them.
 */
class EventHasUser extends \Hubleto\App\External\WaiBlue\HlthcrCore\Models\HlthcrModel
{
  public string $table = 'hlthcr_event_has_users';
  public string $recordManagerClass = RecordManagers\EventHasUser::class;
  public ?string $lookupSqlValue = '(select concat(first_name, " ", last_name) from users where users.id = {%TABLE%}.id_user)';
  public ?string $lookupUrlDetail = 'hlthcr-events/event-has-users/{%ID%}';

  public array $relations = [
    'EVENT' => [ self::BELONGS_TO, Event::class, 'id_event', 'id' ],
    'USER' => [ self::BELONGS_TO, User::class, 'id_user', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_event' => (new Lookup($this, $this->translate('Event'), Event::class, 'CASCADE'))->setRequired()->setDefaultVisible(),
      'id_user' => (new Lookup($this, $this->translate('User'), User::class))->setRequired()->setDefaultVisible(),
    ]);
  }

  // A JSON list of the event's attendee ids, e.g. "[3,5]" (the format of the event form).
  public static function getAttendeeIdsSql(string $eventIdColumn): string
  {
    return 'select concat("[", ifnull(group_concat(ehu.id_user order by ehu.id_user), ""), "]")
      from hlthcr_event_has_users ehu where ehu.id_event = ' . $eventIdColumn;
  }

  // Only active users of the service can attend (one legacy app lists them); other ids are left out.
  public function saveAttendees(int $idEvent, array $attendeeUserIds): void
  {
    $activeAttendeeIds = $this->getModel(User::class)->record
      ->whereIn('id', $attendeeUserIds)
      ->where('is_active', 1)
      ->where('type', '<>', User::TYPE_EXTERNAL)
      ->pluck('id')
      ->toArray()
    ;

    $this->record->where('id_event', $idEvent)->delete();
    foreach ($activeAttendeeIds as $idUser) {
      $this->record->create(['id_event' => $idEvent, 'id_user' => $idUser]);
    }
  }
}
