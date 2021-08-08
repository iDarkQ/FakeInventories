<?php

namespace fakeinventories\fakeinventory;

use pocketmine\scheduler\TaskScheduler;

class FakeInventoryManager {

    /** @var FakeInventory[] */
    private static array $playerInventories = [];
    private static bool $sendPacket = true;
    private static TaskScheduler $scheduler;

    public static function init(TaskScheduler $scheduler) : void {
        self::$scheduler = $scheduler;
    }

    public static function getInventory(string $nick) : ?FakeInventory {
        return self::$playerInventories[$nick] ?? null;
    }

    public static function isOpening(string $nick) : bool {
        return isset(self::$playerInventories[$nick]);
    }

    public static function setInventory(string $player, FakeInventory $inv) : void {
        self::$playerInventories[$player] = $inv;
    }

    public static function unsetInventory(string $nick) : void {
        unset(self::$playerInventories[$nick]);
    }

    public static function getInventories() : array {
        return self::$playerInventories;
    }

    public static function getScheduler() : TaskScheduler {
        return self::$scheduler;
    }

    public static function setSendPacket(bool $value) : void {
        self::$sendPacket = $value;
    }

    public static function hasSendPacket() : bool {
        return self::$sendPacket;
    }
}