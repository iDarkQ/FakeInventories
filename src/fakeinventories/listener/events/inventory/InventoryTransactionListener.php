<?php

namespace fakeinventories\listener\events\inventory;

use fakeinventories\fakeinventory\FakeInventory;
use fakeinventories\fakeinventory\FakeInventoryManager;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerUIInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;

class InventoryTransactionListener implements Listener {

    /**
     * @param InventoryTransactionEvent $e
     * @priority NORMAL
     * @ignoreCancelled true
     */

    public function onTransaction(InventoryTransactionEvent $e) : void {
        $transaction = $e->getTransaction();
        $player = $transaction->getSource();
        $inventories = $transaction->getInventories();
        $actions = $transaction->getActions();

        $fakeInventory = FakeInventoryManager::getInventory($player->getName());

        foreach($inventories as $inventory) {
            if($inventory instanceof FakeInventory) {
                if($fakeInventory === null) {
                    $e->setCancelled(true);
                    return;
                }

                foreach($actions as $action) {
                    if(!$action instanceof SlotChangeAction)
                        continue;
                    if($action->getInventory() instanceof PlayerInventory || $action->getInventory() !== $inventory)
                        continue;

                    $e->setCancelled($fakeInventory->onTransaction($player, $action->getSourceItem(), $action->getTargetItem(), $action->getSlot()));
                }
            }
        }
    }
}
