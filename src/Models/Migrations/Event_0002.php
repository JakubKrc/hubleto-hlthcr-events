<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\Migrations;

use Hubleto\Framework\Migration;

// A car with bookings cannot be deleted, so the bookings stay.
class Event_0002 extends Migration
{
  public function upgradeSchema(): void
  {
  }

  public function downgradeSchema(): void
  {
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` DROP FOREIGN KEY `fk__hlthcr_events__id_car`");
    $this->db->execute("ALTER TABLE `hlthcr_events` ADD CONSTRAINT `fk__hlthcr_events__id_car` FOREIGN KEY (`id_car`) REFERENCES `hlthcr_cars` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` DROP FOREIGN KEY `fk__hlthcr_events__id_car`");
    $this->db->execute("ALTER TABLE `hlthcr_events` ADD CONSTRAINT `fk__hlthcr_events__id_car` FOREIGN KEY (`id_car`) REFERENCES `hlthcr_cars` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
  }
}
