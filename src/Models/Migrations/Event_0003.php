<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\Migrations;

use Hubleto\Framework\Migration;

// Foreign key of the event's user (models and lookups review B6).
class Event_0003 extends Migration
{
  public function upgradeSchema(): void
  {
  }

  public function downgradeSchema(): void
  {
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` ADD CONSTRAINT `fk__hlthcr_events__id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` DROP FOREIGN KEY `fk__hlthcr_events__id_user`");
  }
}
