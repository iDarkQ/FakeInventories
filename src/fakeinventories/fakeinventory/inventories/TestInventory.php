<?php

namespace fakeinventories\fakeinventory\inventories;

use fakeinventories\fakeinventory\FakeInventory;
use pocketmine\item\Item;
use pocketmine\Player;

class TestInventory extends FakeInventory {

    public function __construct(Player $player) {
        parent::__construct($player, "Test1", self::LARGE_CHEST);
    }

    public function setItems() : void {
        $this->setItem(0, Item::get(Item::CHEST)->setCustomName("Test1"));
    }

    public function onTransaction(Player $player, Item $sourceItem, Item $targetItem, int $slot) : bool {

        if($sourceItem->getId() === Item::CHEST)
            $this->changeInventory($player, (new TestInventory2($player)));

        $this->unClickItem($player);
        return true;
    }
}