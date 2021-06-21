<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates;

use Exception;
use GrosserZak\PortableCrates\Utils\PortableCrate;
use JetBrains\PhpStorm\Pure;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as G;

class PCManager {

    const PREFIX = G::DARK_GRAY . "[" . G::DARK_GREEN . "Portable" . G::GREEN . "Crates" . G::DARK_GRAY . "]";

    /** @var Main */
    private Main $plugin;

    /** @var PortableCrate[] */
    private array $crates = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadCrates();
    }

    public function loadCrates() : void {
        $this->crates = [];
        foreach($this->plugin->getCratesCfg()->getAll() as $index => $data) {
            $crateItem = $this->getCrateItemByData($data);
            $this->crates[$data["name"]] = new PortableCrate($data["name"], $index, $crateItem, $data["id"], $data["rewards"]);
        }
    }

    public function getCrates() : array {
        return $this->crates;
    }

    /**
     * This function returns the config data of a crate given its config index
     * @param int $index
     * @return array|null
     * An array, containing the crate data, if it exists in the config file, null otherwise
     */
    private function getCrateConfigDataByIndex(int $index) : ?array {
        return $this->plugin->getCratesCfg()->get($index) ?? null;
    }

    /**
     * This function returns the PortableCrate of a crate by its name
     * @param string $name
     * @return PortableCrate|null
     * A PortableCrate instance if the crate has been found, null otherwise
     */
    #[Pure]
    public function existsCrate(string $name) : ?PortableCrate {
        return $this->crates[strtolower($name)] ?? null;
    }

    /**
     * This function updates a crate when a reward has been added or removed
     * @param PortableCrate $crate
     */
    private function updateCrate(PortableCrate $crate) : void {
        $data = $this->getCrateConfigDataByIndex($crate->getConfigIndex());
        $crateItem = $this->getCrateItemByData($data);
        $this->crates[$crate->getName()] = new PortableCrate($crate->getName(), $crate->getConfigIndex(), $crateItem, $data["id"], $data["rewards"]);
    }

    /**
     * This function creates a new crate based on the item held by the player
     * @param Item $crateItem Item held by the player
     * @param string $crateName The name for the crate
     * @throws Exception
     */
    public function createNewCrate(Item $crateItem, string $crateName) : void {
        $cratesCfg = $this->plugin->getCratesCfg();
        $newCrateIndex = array_key_last($cratesCfg->getAll()) + 1;
        $newCrate = array(
            "name" => $crateName,
            "id" => substr(sha1(random_bytes(8)), 0, 8),
            "item" => [$crateItem->getId(), $crateItem->getDamage()],
            "customname" => $crateItem->getName(),
            "lore" => $crateItem->getLore(),
            "rewards" => []
        );
        $cratesCfg->set($newCrateIndex, $newCrate);
        $cratesCfg->save();
        $this->loadCrates();
    }

    /**
     * This function deletes a crate
     * @param string $name
     * @return bool
     * True if the crate has been deleted, false otherwise
     */
    public function deleteCrateByName(string $name) : bool {
        $cratesCfg = $this->plugin->getCratesCfg();
        if(!isset($this->crates[strtolower($name)])) return false;
        $crate = $this->crates[strtolower($name)];
        $cratesCfg->remove($crate->getConfigIndex());
        $cratesCfg->save();
        $this->loadCrates();
        return true;
    }

    /**
     * THis function adds a reward to a crate
     * @param PortableCrate $crate The crate which will be added the reward to
     * @param Item $item Item held by the player
     * @param int $prob The probability to be given by the crate
     * @throws Exception
     */
    public function addRewardToCrate(PortableCrate $crate, Item $item, int $prob) : void {
        $crateIndex = $crate->getConfigIndex();
        $cratesCfg = $this->plugin->getCratesCfg();
        $rewards = $cratesCfg->getNested($crateIndex . ".rewards");
        $rewards[] = [$item->getId(), $item->getDamage(), $item->getCount(), $item->getName(), $item->getLore(), base64_encode($item->getCompoundTag()), $prob];
        $cratesCfg->setNested($crateIndex . ".rewards", $rewards);
        $cratesCfg->setNested($crateIndex . ".id", substr(sha1(random_bytes(8)), 0, 8));
        $cratesCfg->save();
        $this->updateCrate($crate);
    }

    /**
     * This function removes a reward from a crate
     * @param PortableCrate $crate The crate that the reward will be removed from
     * @param int $rewardIndex The index of the reward
     * @return string
     * The result operation message that will be sent to the command executor
     * @throws Exception
     */
    public function removeRewardFromCrate(PortableCrate $crate, int $rewardIndex) : string {
        $crateIndex = $crate->getConfigIndex();
        $cratesCfg = $this->plugin->getCratesCfg();
        $lastKey = array_key_last($crate->getRewards());
        if($rewardIndex > $lastKey) {
            return G::RED . " The reward index must be " . ($lastKey === 0 ? "1" : "between 1 and " . ucfirst($crate->getName()) . " Crate max reward index of " . ($lastKey + 1)) . "!";
        }
        $rewards = $cratesCfg->getNested($crateIndex . ".rewards");
        array_splice($rewards, $rewardIndex, 1);
        $cratesCfg->setNested($crateIndex . ".rewards", $rewards);
        $cratesCfg->setNested($crateIndex . ".id", substr(sha1(random_bytes(8)), 0, 8));
        $cratesCfg->save();
        $this->updateCrate($crate);
        return G::GREEN . " You've removed the reward with index " . ($rewardIndex+1) . ": x" . $rewards[$rewardIndex][2] . " " . $rewards[$rewardIndex][3] . G::RESET . G::GREEN . " from " . ucfirst($crate->getName()) . " Crate";
    }

    /**
     * This function is used to load the crate item on plugin start or when one has been updated
     * (When a reward has been added/removed)
     * @param array $data Config data
     * @return Item The result item
     */
    private function getCrateItemByData(array $data) : Item {
        $crateItem = Item::get($data["item"][0], $data["item"][1]);
        $tag = new CompoundTag("", [
            new CompoundTag("PortableCrates", [
                new StringTag("Name", $data["name"]),
                new StringTag("Id", $data["id"])
            ])
        ]);
        $crateItem->setNamedTag($tag);
        $crateItem->setCustomName(G::RESET . $data["customname"]);
        $crateItem->setLore(array_merge($data["lore"], ["", G::RESET . G::GRAY . "(Click to open)", G::RESET . G::GRAY . "(Shift-Click to view rewards)"]));
        return $crateItem;
    }

    /**
     * This function is used to give a player a crate
     * @param Player $sender The sender
     * @param Player $player The receiver
     * @param Item $crateItem The crate to give
     * @param bool $giveOnWorld If set to true the crate will be given only if the player is on the same world of the sender
     * @return bool true if the item has been successfully given, false otherwise
     */
    public function giveCrate(Player $sender, Player $player, Item $crateItem, bool $giveOnWorld = false) : bool{
        if($giveOnWorld){
            if($sender->getLevel()->getFolderName() !== $player->getLevel()->getFolderName()){
                return false;
            }
        }
        $playerInv = $player->getInventory();
        if($playerInv->canAddItem($crateItem)){
            $playerInv->addItem($crateItem);
            $player->sendMessage($this::PREFIX . G::GRAY . " You have received: " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
        } else {
            $pos = new Vector3($player->getX(), $player->getY(), $player->getZ());
            $player->getLevel()->dropItem($pos, $crateItem);
            $player->sendMessage($this::PREFIX . G::WHITE . " x" . $crateItem->getCount() . " " . $crateItem->getCustomName() . G::RESET . G::RED . " Has been dropped on the ground because your inventory is full!");
        }
        return true;
    }

    /**
     * This function gives a crate reward (used onInteractEvent when a player opens a crate)
     * @param array $reward
     * @param Player $player
     */
    public function giveCrateReward(array $reward, Player $player) : void {
        $item = Item::get((int)$reward[0], (int)$reward[1], (int)$reward[2]);
        $item->setNamedTag(new CompoundTag(base64_decode($reward[5])));
        $item->setCustomName($reward[3]);
        $item->setLore($reward[4]);
        if(!$player->getInventory()->canAddItem($item)) {
            $player->getLevel()->dropItem(new Vector3($player->getX(), $player->getY(), $player->getZ()), $item);
            $player->sendMessage(TextFormat::RED . "Your inventory is full! " .  $item->getCustomName() . TextFormat::RESET . TextFormat::RED . " dropped on the ground.");
        } else {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * @param Player $player
     * @param PortableCrate $crate
     * @return bool true if the InvMenu of the crate has been found and sent to the player, false otherwise
     */
    public function openCrateRewardsGUI(Player $player, PortableCrate $crate) : bool {
        if(($gui = $crate->getRewardGUI()) === null) return false;
        $gui->send($player);
        return true;
    }
}