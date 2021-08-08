<?php

namespace fakeinventories\listener\events\packet;

use fakeinventories\fakeinventory\FakeInventoryManager;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\scheduler\ClosureTask;

class DataPacketReceiveListener implements Listener {

    /**
     * @param DataPacketReceiveEvent $e
     * @ignoreCancelled true
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $e) : void{
        if($e->getPacket() instanceof ContainerClosePacket){
            FakeInventoryManager::setSendPacket(false);
            $e->getPlayer()->sendDataPacket($e->getPacket(), false, true);
            FakeInventoryManager::setSendPacket(true);
        }
    }

    /**
     * @param DataPacketReceiveEvent $e
     * @ignoreCancelled true
     */
    public function fakeInventory(DataPacketReceiveEvent $e) : void {
        $player = $e->getPlayer();
        $packet = $e->getPacket();

        if($packet instanceof ContainerClosePacket) {
            if(($fakeInventory = FakeInventoryManager::getInventory($player->getName())) !== null) {
                FakeInventoryManager::getScheduler()->scheduleTask(new ClosureTask(function() use ($player, $fakeInventory) : void {
                    if($fakeInventory->hasChanged() && $fakeInventory->isClosed()) {
                        $fakeInventory->nextInventory->openFor([$player]);
                    }
                }));
            }
        }
    }
}