<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\Migrations;

use Hubleto\Framework\Migration;

// Attendees move from a JSON list in hlthcr_events.attendee_user_ids to a junction table (models and lookups review C2).
class EventHasUser_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("CREATE TABLE IF NOT EXISTS `hlthcr_event_has_users` (
      `id` int(8) primary key auto_increment,
      `id_event` int(8) NOT NULL,
      `id_user` int(8) NOT NULL,
      UNIQUE INDEX `event_has_user` (`id_event`, `id_user`),
      INDEX `id_user` (`id_user`)
    ) ENGINE=InnoDB");

    if (!$this->eventsHaveJsonAttendees()) return;

    // Only ids of existing events and users, so the foreign keys can be added.
    $this->db->execute("INSERT IGNORE INTO `hlthcr_event_has_users` (`id_event`, `id_user`)
      SELECT e.id, attendee.id_user
      FROM `hlthcr_events` e
      JOIN JSON_TABLE(IF(JSON_VALID(e.attendee_user_ids), e.attendee_user_ids, '[]'), '$[*]' COLUMNS (`id_user` int PATH '$')) attendee
      WHERE attendee.id_user IN (SELECT `id` FROM `users`)");
    $this->db->execute("ALTER TABLE `hlthcr_events` DROP COLUMN `attendee_user_ids`");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `hlthcr_event_has_users`");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_event_has_users` ADD CONSTRAINT `fk__hlthcr_event_has_users__id_event` FOREIGN KEY (`id_event`) REFERENCES `hlthcr_events` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
    $this->db->execute("ALTER TABLE `hlthcr_event_has_users` ADD CONSTRAINT `fk__hlthcr_event_has_users__id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_event_has_users` DROP FOREIGN KEY `fk__hlthcr_event_has_users__id_event`");
    $this->db->execute("ALTER TABLE `hlthcr_event_has_users` DROP FOREIGN KEY `fk__hlthcr_event_has_users__id_user`");
  }

  private function eventsHaveJsonAttendees(): bool
  {
    $columns = $this->db->fetchAll(
      "SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hlthcr_events' AND COLUMN_NAME = 'attendee_user_ids'"
    );
    return count($columns) > 0;
  }
}
