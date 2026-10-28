<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEvents\Models\Migrations;

use Hubleto\Framework\Migration;

class Event_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("CREATE TABLE IF NOT EXISTS `hlthcr_events` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) NOT NULL,
      `date_start` date NULL DEFAULT NULL,
      `time_start` time NULL DEFAULT NULL,
      `date_end` date NULL DEFAULT NULL,
      `time_end` time NULL DEFAULT NULL,
      `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
      `id_user` int(8) NULL DEFAULT NULL,
      `id_car` int(8) NULL DEFAULT NULL,
      `is_executed` tinyint(1) NOT NULL DEFAULT 0,
      `note` varchar(255) NULL DEFAULT NULL,
      `is_public` tinyint(1) NOT NULL DEFAULT 0,
      `attendee_user_ids` text NULL DEFAULT NULL,
      INDEX `id_user` (`id_user`),
      INDEX `id_car` (`id_car`),
      INDEX `date_start` (`date_start`),
      INDEX `date_end` (`date_end`)
    ) ENGINE=InnoDB");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `hlthcr_events`");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` ADD CONSTRAINT `fk__hlthcr_events__id_car` FOREIGN KEY (`id_car`) REFERENCES `hlthcr_cars` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_events` DROP FOREIGN KEY `fk__hlthcr_events__id_car`");
  }
}
