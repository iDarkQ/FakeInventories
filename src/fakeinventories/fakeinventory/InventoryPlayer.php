<?php

namespace fakeinventories\fakeinventory;

use pocketmine\Player;

class InventoryPlayer extends Player {

    public function getOpenedWindows() : array {
        return $this->windowIndex;
    }
}