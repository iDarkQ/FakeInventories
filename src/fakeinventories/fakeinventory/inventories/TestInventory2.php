<?php

namespace fakeinventories\fakeinventory\inventories;

use fakeinventories\fakeinventory\FakeInventory;
use pocketmine\item\Item;
use pocketmine\Player;

class TestInventory2 extends FakeInventory {

    public function __construct(Player $player) {
        parent::__construct($player, "Test2", self::SMALL_CHEST);
    }

    public function setItems() : void {
        $this->setItem(0, Item::get(Item::CHEST)->setCustomName("Test2"));
    }

    public function onTransaction(Player $player, Item $sourceItem, Item $targetItem, int $slot) : bool {

        if($sourceItem->getId() === Item::CHEST)
            $this->changeInventory($player, (new TestInventory($player)));

        $this->unClickItem($player);
        return true;
    }
}