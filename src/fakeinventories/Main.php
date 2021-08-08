<?php

namespace fakeinventories;

use fakeinventories\fakeinventory\FakeInventoryManager;
use fakeinventories\listener\ListenerManager;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    private static self $instance;

    public function onEnable() : void {
        self::$instance = $this;

        ListenerManager::init($this);
        FakeInventoryManager::init($this->getScheduler());
    }

    public static function getInstance() : self {
        return self::$instance;
    }
}