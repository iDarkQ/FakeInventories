<?php

namespace fakeinventories\fakeinventory;

use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

abstract class FakeInventory extends ContainerInventory implements FakeInventoryPatterns, FakeInventorySize {

    protected $holder;
    protected $title;
    protected int $size;
    protected ?Player $player;
    protected bool $isClosed = false;
    protected bool $hasChanged = false;
    protected bool $inventoryBehindPlayer;

    public ?self $nextInventory = null;

    /** @var Vector3[] */
    private array $chests = [];

    public static function getBlockBehindPlayer(Player $player) : Vector3 {
        switch($player->getDirection()) {
            case 0:
                return $player->asVector3()->floor()->subtract(2);
            case 1:
                return $player->asVector3()->floor()->subtract(0, 0, 2);
            case 2:
                return $player->asVector3()->floor()->add(2);
            case 3;
                return $player->asVector3()->floor()->add(0, 0, 2);
            default:
                return $player->asVector3()->floor();
        }
    }

    public function __construct(?Player $player, string $title, int $size = self::SMALL_CHEST, bool $inventoryBehindPlayer = true) {
        parent::__construct(($holder = new Vector3()), [], $size, $title);

        $this->player = $player;
        $this->holder = $holder;
        $this->title = $title;
        $this->size = $size;
        $this->inventoryBehindPlayer = $inventoryBehindPlayer;
        $this->hasChanged = false;
        $this->nextInventory = $this;

        $this->clearAll();
        $this->setItems();
    }

    abstract public function setItems() : void;

    abstract public function onTransaction(Player $player, Item $sourceItem, Item $targetItem, int $slot) : bool;

    public function onClose(Player $who) : void {
        $this->isClosed = true;

        parent::onClose($who);

        if(!$this->hasChanged)
            $this->closeFor($who);

        FakeInventoryManager::unsetInventory($who->getName());

        if(Server::getInstance()->isRunning()) {
            FakeInventoryManager::getScheduler()->scheduleTask(new ClosureTask(function() : void {
                $this->hasChanged = false;
            }));
        }
    }

    public function closeFor(Player $player) : void {
        foreach($this->chests as $key => $position) {
            $block = $player->getLevel()->getBlock($position);

            $pk1 = new UpdateBlockPacket();
            $pk1->x = $position->x;
            $pk1->y = $position->y;
            $pk1->z = $position->z;
            $pk1->flags = UpdateBlockPacket::FLAG_NETWORK;
            $pk1->blockRuntimeId = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());

            $player->dataPacket($pk1);
        }

        parent::onClose($player);
    }

    public function openFor(array $players) : void {
        if($this->size === self::LARGE_CHEST)
            $this->hasChanged = true;

        foreach($players as $player) {
            foreach($player->getOpenedWindows() as $key => $window) {
                if($window instanceof FakeInventory)
                    $player->removeWindow($window);
            }
        }

        $chestPosition = $this->getChestPosition($players);

        $pos = $chestPosition->add(0, 2);
        $this->holder = new Vector3($pos->x, $pos->y, $pos->z);

        $this->sendAll($players, $pos);

        foreach($players as $player) {
            FakeInventoryManager::setInventory($player->getName(), $this);
            $player->addWindow($this);
        }
    }

    public function changeInventory(Player $player, FakeInventory $inventory) : void {
        $this->isClosed = true;

        $this->nextInventory = clone $inventory;

        if($this->size !== $inventory->getSize()) {
            $inventory->hasChanged = true;
            $this->hasChanged = true;

            FakeInventoryManager::getScheduler()->scheduleTask(new ClosureTask(function() use ($player) : void {
                $this->closeFor($player);
            }));
        } else {
            if((!$this->holder->equals($this->getChestPosition([$player])))) {
                FakeInventoryManager::getScheduler()->scheduleTask(new ClosureTask(function() use ($player) : void {
                    $this->closeFor($player);
                }));
            }
        }

        FakeInventoryManager::getScheduler()->scheduleTask(new ClosureTask(function() use ($inventory, $player) : void {
            $inventory->openFor([$player]);
        }));
    }

    protected function inventoryPacket(Vector3 $position) : DataPacket {
        $pk = new UpdateBlockPacket();
        $pk->x = $position->x;
        $pk->y = $position->y;
        $pk->z = $position->z;
        $pk->flags = UpdateBlockPacket::FLAG_ALL;
        $pk->blockRuntimeId = BlockFactory::toStaticRuntimeId(54);

        $this->chests[] = $position;
        return $pk;
    }

    protected function inventoryNamePacket(Vector3 $position) : DataPacket {
        $writer = new NetworkLittleEndianNBTStream();

        $pk = new BlockActorDataPacket;
        $pk->x = $position->x;
        $pk->y = $position->y;
        $pk->z = $position->z;

        ($tag = new CompoundTag())->setString('CustomName', $this->title);
        $pk->namedtag = $writer->write($tag);
        return $pk;
    }

    protected function pairInventories(Vector3 $position) : DataPacket {
        $tag = new CompoundTag();
        $tag->setInt('pairx', $position->x);
        $tag->setInt('pairz', $position->z);

        $writer = new NetworkLittleEndianNBTStream();
        $pk = new BlockActorDataPacket;
        $pk->x = ($pairPos = $position->add(1))->x;
        $pk->y = $pairPos->y;
        $pk->z = $pairPos->z;
        $pk->namedtag = $writer->write($tag);
        return $pk;
    }

    public function sendAll(array $players, Vector3 $vector3) : void {

        $position = clone $vector3;
        $batch = new BatchPacket();

        $batch->addPacket($this->inventoryPacket($position));

        if($this->size === self::LARGE_CHEST)
            $batch->addPacket($this->inventoryPacket($position->add(1)));

        $batch->addPacket($this->inventoryNamePacket($vector3));

        if($this->size === self::LARGE_CHEST)
            $batch->addPacket($this->pairInventories($vector3));

        $batch->encode();

        foreach($players as $player)
            $player->dataPacket($batch);
    }

    public function getChestPosition(array $players) : Vector3 {
        $chestPosition = new Vector3();

        if(count($players) <= 1) {
            foreach($players as $player) {
                $chestPosition = self::getBlockBehindPlayer($player);

                if(!$this->inventoryBehindPlayer)
                    $chestPosition = $player->asVector3()->floor();
            }
        } else {
            $x = round(($players[0]->x + $players[1]->x) / 2);
            $y = round(($players[0]->y + $players[1]->y) / 2);
            $z = round(($players[0]->z + $players[1]->z) / 2);

            $chestPosition = (new Vector3($x, $y, $z))->floor();
        }

        return $chestPosition;
    }

    public function unClickItem(Player $player) : void {
        $packet = new InventorySlotPacket();
        $packet->windowId = ContainerIds::UI;
        $packet->inventorySlot = 0;
        $packet->item = ItemStackWrapper::legacy(Item::get(Item::AIR));
        $player->sendDataPacket($packet);
    }

    public function fill(int $itemId = ItemIds::IRON_BARS) : void {
        for($i = 0; $i < $this->getSize(); $i++)
            if($this->isSlotEmpty($i))
                $this->setItem($i, Item::get($itemId)->setCustomName(" "));
    }

    public function fillWithPattern(array $pattern, int $itemId = ItemIds::IRON_BARS) : void {
        foreach($pattern as $slot)
            $this->setItem($slot, Item::get($itemId)->setCustomName(" "));
    }

    public function setItem(int $index, Item $item, bool $send = true, bool $reset = false) : bool {
        if($reset && $item->getId() !== Item::AIR && $item->getCustomName() !== "") {
            $item->setCustomName("§r" . $item->getCustomName());
        }

        return parent::setItem($index, $item, $send);
    }

    public function setItemAt(int $x, int $y, Item $item, bool $send = true, bool $reset = true) : void {
        $this->setItem((9 * $y - (9 - $x)) - 1, $item, $send, $reset);
    }

    public function getItemAt(int $x, int $y) : Item {
        return $this->getItem((9 * $y - (9 - $x)) - 1);
    }

    public function getSlotAt(int $x, int $y) : int {
        return (9 * $y - (9 - $x)) - 1;
    }

    public function getNetworkType() : int {
        return WindowTypes::CONTAINER;
    }

    public function getName() : string {
        return "Fake Inventory";
    }

    public function getTitle() : string {
        return $this->title;
    }

    public function setTitle(string $title) : void {
        $this->title = $title;
    }

    public function getDefaultSize() : int {
        return $this->size;
    }

    public function getHolder() : Vector3 {
        return $this->holder;
    }

    public function isClosed() : bool {
        return $this->isClosed;
    }

    public function isChanging() : bool {
        return $this->hasChanged;
    }

    public function hasChanged() : bool {
        return $this->hasChanged;
    }
}