<?php

namespace fakeinventories\listener;

use fakeinventories\listener\events\inventory\InventoryTransactionListener;
use fakeinventories\listener\events\packet\DataPacketReceiveListener;
use fakeinventories\listener\events\packet\DataPacketSendListener;
use fakeinventories\listener\events\player\PlayerCreationListener;
use fakeinventories\Main;

class ListenerManager {

    public static function init(Main $main) : void {
        $listeners = [
            new InventoryTransactionListener(),
            new DataPacketReceiveListener(),
            new DataPacketSendListener(),
            new PlayerCreationListener()
        ];

        foreach($listeners as $listener)
            $main->getServer()->getPluginManager()->registerEvents($listener, $main);
    }
}