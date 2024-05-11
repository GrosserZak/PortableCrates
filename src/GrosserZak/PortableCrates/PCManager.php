<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates;

use Exception;
use GrosserZak\PortableCrates\Utils\PortableCrate;
use GrosserZak\PortableCrates\Utils\RewardGUI;
use JsonException;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as G;

class PCManager {

    const PREFIX = G::DARK_GRAY . "[" . G::DARK_GREEN . "Portable" . G::GREEN . "Crates" . G::DARK_GRAY . "]";

    /** @var int Represents the max size of the inventory to display crate rewards */
    private const MAX_SIZE = 45;

    /** @var PortableCrate[] */
    private array $crates = [];

    /** @var array<string, int, list<Item>> */
    private static array $contents = [];

    public function __construct(
        private readonly Config $cratesCfg
    ) {
        $this->loadCrates();
    }

    public function loadCrates() : void {
        $this->crates = [];
        foreach($this->cratesCfg->getAll() as $index => $data) {
            $crateItem = $this->getCrateItemByData($data);
            $this->crates[strtolower($data["name"])] = new PortableCrate($data["name"], (string)$index, $crateItem, $data["id"], $data["rewards"]);
            self::initRewardsGUIContents(strtolower($data["name"]), array_chunk($data["rewards"], self::MAX_SIZE));
        }
    }

    private function initRewardsGUIContents(string $crateName, array $rewardsArr) : void {
    self::$contents[$crateName] = [];
    foreach($rewardsArr as $page => $rewards) {
        for($i=0;$i<54;$i++) {
                if($i>=self::MAX_SIZE) {
                    if($i == 46 and isset($rewardsArr[($page-1)])) {
                        $item = VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem();
                        $item->setNamedTag(CompoundTag::create()->setTag("PortableCrates",
                            CompoundTag::create()->setTag("RewardGUI",
                                CompoundTag::create()->setString("GoToPage", "Previous")
                            )
                        ));
                        $item->setCustomName(G::RESET . G::RED . "Previous Page");
                    } elseif($i == 49) {
                        $item = VanillaItems::PAPER();
                        $item->setNamedTag(CompoundTag::create()->setTag("PortableCrates",
                            CompoundTag::create()->setTag("RewardGUI",
                                CompoundTag::create()->setByte("PageNumber", $page)
                            )
                        ));
                        $item->setCustomName(G::RESET . G::WHITE . "Page: " . ($page+1));
                    } elseif($i == 52 and isset($rewardsArr[($page+1)])) {
                        $item = VanillaBlocks::WOOL()->setColor(DyeColor::GREEN())->asItem();
                        $item->setNamedTag(CompoundTag::create()->setTag("PortableCrates",
                            CompoundTag::create()->setTag("RewardGUI",
                                CompoundTag::create()->setString("GoToPage", "Next")
                            )
                        ));
                        $item->setCustomName(G::RESET . G::GREEN . "Next Page");
                    } else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem()->setCustomName(G::RESET);
                    }
                } else (!isset($rewards[$i])) {
                    $item = VanillaBlocks::BARRIER()->asItem()->setCustomName(G::RESET);
            } else {
                $reward = $rewards[$i];
                $count = (int)$reward[1];
                $item = StringToItemParser::getInstance()->parse($reward[0]);

                if ($item !== null) {
                    $item->setCount($count <= 64 ? $count : 1)
                    ->setCustomName(G::RESET . G::WHITE . "x" . $count . " " . $reward[2])
                    ->setLore(array_merge($reward[3], ["", G::RESET . G::GREEN . $reward[5] . "% probability"]));
                    self::$contents[$crateName][$page][] = $item;
                }
            }
        } // This is the missing closing bracket
    }
}

    public function getCrates() : array {
        return $this->crates;
    }

    /**
     * This function returns the config data of a crate given its config index
     * @param string $index The config index of the crate
     * @return array|null
     * An array containing the crate data if the crate exists in the config file, null otherwise
     */
    private function getCrateConfigDataByIndex(string $index) : ?array {
        return $this->cratesCfg->get($index) ?? null;
    }

    /**
     * This function returns the PortableCrate instance of a crate by its name
     * @param string $name The crate name
     * @return PortableCrate|null
     * A PortableCrate instance if the crate has been found, null otherwise
     */
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
        $this->crates[strtolower($crate->getName())] = new PortableCrate($crate->getName(), $crate->getConfigIndex(), $crateItem, $data["id"], $data["rewards"]);
        self::initRewardsGUIContents(strtolower($crate->getName()), array_chunk($data["rewards"], self::MAX_SIZE));
    }

    /**
     * This function creates a new crate based on the item held by the player
     * @param Item $crateItem The item held by the player
     * @param string $crateName The name for the crate
     * @throws Exception
     */
    public function createNewCrate(Item $crateItem, string $crateName) : void {
        $cratesCfg = $this->cratesCfg;
        $newCrateIndex = array_key_last($cratesCfg->getAll()) + 1;
        $newCrate = array(
            "name" => $crateName,
            "id" => substr(sha1(random_bytes(8)), 0, 8),
            "item" => StringToItemParser::getInstance()->lookupAliases($crateItem)[0],
            "customname" => $crateItem->getName(),
            "lore" => $crateItem->getLore(),
            "rewards" => []
        );
        $cratesCfg->set((string)$newCrateIndex, $newCrate);
        $cratesCfg->save();
        $this->loadCrates();
    }

    /**
     * This function deletes a crate
     * @param string $name The crate name
     * @return bool
     * True if the crate has been deleted, false otherwise
     * @throws JsonException
     */
    public function deleteCrateByName(string $name) : bool {
        $cratesCfg = $this->cratesCfg;
        if(!isset($this->crates[strtolower($name)])) return false;
        $crate = $this->crates[strtolower($name)];
        $cratesCfg->remove($crate->getConfigIndex());
        $cratesCfg->save();
        $this->loadCrates();
        return true;
    }

    /**
     * This function adds a reward to a crate
     * @param PortableCrate $crate The crate which will be added the reward to
     * @param Item $item The reward item held by the player
     * @param int $prob The probability of the reward to be found in the crate
     * @throws Exception
     */
    public function addRewardToCrate(PortableCrate $crate, Item $item, int $prob, int $count) : void {
        $crateIndex = $crate->getConfigIndex();
        $cratesCfg = $this->cratesCfg;
        $rewards = $cratesCfg->getNested($crateIndex . ".rewards");
        $rewards[] = [StringToItemParser::getInstance()->lookupAliases($item)[0], $count, $item->getName(), $item->getLore(), base64_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($item->getNamedTag(), ""))), $prob];
        $cratesCfg->setNested($crateIndex . ".rewards", $rewards);
        $cratesCfg->setNested($crateIndex . ".id", substr(sha1(random_bytes(8)), 0, 8));
        $cratesCfg->save();
        $this->updateCrate($crate);
    }

    /**
     * This function removes a reward from a crate
     * @param PortableCrate $crate The crate that the reward will be removed from
     * @param int $rewardIndex The index of the reward to be removed
     * @return string
     * The result operation message that will be sent to the command executor
     * @throws Exception
     */
    public function removeRewardFromCrate(PortableCrate $crate, int $rewardIndex) : string {
        $crateIndex = $crate->getConfigIndex();
        $cratesCfg = $this->cratesCfg;
        $lastKey = array_key_last($crate->getRewards());
        if($rewardIndex > $lastKey) {
            return G::RED . " The reward index must be " . ($lastKey === 0 ? "1" : "between 1 and " . $crate->getName() . " Crate max reward index of " . ($lastKey + 1)) . "!";
        }
        $rewards = $cratesCfg->getNested($crateIndex . ".rewards");
        if(count($rewards) === 0) {
            return G::RED . " There's no rewards to be removed from this crate!";
        }
        $removedReward = array_splice($rewards, $rewardIndex, 1)[0];
        $cratesCfg->setNested($crateIndex . ".rewards", $rewards);
        $cratesCfg->setNested($crateIndex . ".id", substr(sha1(random_bytes(8)), 0, 8));
        $cratesCfg->save();
        $this->updateCrate($crate);
        return G::GREEN . " You've removed the reward with index " . ($rewardIndex+1) . ": x" . $removedReward[2] . " " . $removedReward[3] . G::RESET . G::GREEN . " with " . $removedReward[6] . "% probability from " . $crate->getName() . " Crate";
    }

    /**
     * This function is used to load the crate item on plugin start or when one has been updated
     * (When a reward has been added/removed)
     * @param array $data The config data of the crate
     * @return Item The result item
     */
    private function getCrateItemByData(array $data) : Item {
        $crateItem = StringToItemParser::getInstance()->parse($data["item"]);
        $crateItem->setNamedTag(CompoundTag::create()
            ->setTag("PortableCrates", CompoundTag::create()
                ->setString("Name", $data["name"])
                ->setString("Id", $data["id"])
            )
        );
        $crateItem->setCustomName(G::RESET . $data["customname"]);
        $crateItem->setLore(array_merge($data["lore"], ["", G::RESET . G::GRAY . "(Click to open)", G::RESET . G::GRAY . "(Shift-Click to view rewards)"]));
        return $crateItem;
    }

    /**
     * This function is used to give a player a crate
     * @param Player $player The player who's going to receive the crate
     * @param Item $crateItem The crate item to be given
     */
    public function giveCrate(Player $player, Item $crateItem) : void {
        $playerInv = $player->getInventory();
        if($playerInv->canAddItem($crateItem)){
            $playerInv->addItem($crateItem);
            $player->sendMessage($this::PREFIX . G::GRAY . " You have received: " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
        } else {
            $pos = $player->getPosition()->asVector3();
            $player->getWorld()->dropItem($pos, $crateItem);
            $player->sendMessage($this::PREFIX . G::WHITE . " x" . $crateItem->getCount() . " " . $crateItem->getCustomName() . G::RESET . G::RED . " Has been dropped on the ground because your inventory is full!");
        }
    }

    /**
     * This function gives a crate reward (used onInteractEvent when a player opens a crate)
     * @param array $reward The reward data
     * @param Player $player The player who's going to receive the reward
     */
    public function giveCrateReward(array $reward, Player $player) : void {
        $item = StringToItemParser::getInstance()->parse($reward[0])->setCount($reward[1]);
        $tag = (new LittleEndianNbtSerializer())->read(base64_decode($reward[4]))->mustGetCompoundTag();
        $item->setNamedTag($tag);
        $item->setCustomName($reward[2]);
        $item->setLore($reward[3]);
        if(!$player->getInventory()->canAddItem($item)) {
            $itemEntity = $player->getWorld()->dropItem($player->getPosition()->asVector3(), $item);
            $itemEntity->setOwner($player->getName());
            $player->sendMessage(TextFormat::RED . "Your inventory is full! " .  $item->getCustomName() . TextFormat::RESET . TextFormat::RED . " dropped on the ground." . G::EOL .
                G::GRAY . "(You have 1 minute to pick it up before other players can collect it!)");
        } else {
            $player->getInventory()->addItem($item);
        }
    }

    public function sendRewardGUI(Player $player, string $crateName) : void {
        (new RewardGUI(self::$contents[$crateName]))->send($player);
    }
}
