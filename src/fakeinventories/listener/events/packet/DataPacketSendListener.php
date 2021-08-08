<?php

namespace fakeinventories\listener\events\packet;

use fakeinventories\fakeinventory\FakeInventoryManager;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class DataPacketSendListener implements Listener {

    /**
     * @param DataPacketSendEvent $e
     * @priority LOW
     * @ignoreCancelled true
     */
    public function onDataPacketSend(DataPacketSendEvent $e) : void{
        if(FakeInventoryManager::hasSendPacket() && $e->getPacket() instanceof ContainerClosePacket)
            $e->setCancelled();
    }
}