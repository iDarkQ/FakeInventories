<?php

namespace fakeinventories\listener\events\player;

use fakeinventories\fakeinventory\InventoryPlayer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;

class PlayerCreationListener implements Listener {

    public function playerCreation(PlayerCreationEvent $e) : void {
        $e->setPlayerClass(InventoryPlayer::class);
    }
}