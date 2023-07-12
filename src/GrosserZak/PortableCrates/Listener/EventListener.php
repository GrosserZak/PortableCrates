<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Listener;

use GrosserZak\PortableCrates\Main;
use GrosserZak\PortableCrates\Utils\WeightedRandom;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as G;

class EventListener implements Listener {

    /** @var array */
    private array $crateCooldown = [];

    public function __construct(
        private readonly Main $plugin
    ) { }

    public function onInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if($item->hasNamedTag()) {
            if(($crateTag = $item->getNamedTag()->getCompoundTag("PortableCrates")) !== null) {
                $pcMgr = $this->plugin->getPCManager();
                $crate = $pcMgr->existsCrate($crateTag->getString("Name"));
                $crateItem = $crate->getItem();
                if($crateTag->getString("Id") !== $crate->getId()) {
                    $crateItem->setCount($item->getCount());
                    $player->getInventory()->setItemInHand(VanillaBlocks::AIR()->asItem());
                    $player->sendMessage(G::GREEN . "The version of your crate was outdated! Now it's updated, Enjoy!");
                    $pcMgr->giveCrate($player, $crateItem);
                    $ev->cancel();
                    return;
                }
                if(empty($crate->getRewards())) {
                    $player->sendMessage(G::RED . "This crate has no rewards in it! " . ($player->hasPermission("portablecrates.command.edit") ? "Add some rewards with \"/pcrate add {$crate->getName()} <prob>\"" : " Please contact an Administrator"));
                    $ev->cancel();
                    return;
                }
                if($player->isSneaking()) {
                    $pcMgr->sendRewardGUI($player, mb_strtolower($crate->getName()));
                    $ev->cancel();
                    return;
                }
                if(time() < ($this->crateCooldown[mb_strtolower($player->getName())] ?? -1)) {
                    $player->sendMessage(G::RED . "You have to wait before opening another crate!");
                    $ev->cancel();
                    return;
                }
                $item->pop();
                $player->getInventory()->setItemInHand($item);
                $this->crateCooldown[mb_strtolower($player->getName())] = time() + 3;
                $message = G::GRAY . $player->getName() . " has opened " . $crateItem->getCustomName() . G::RESET . G::GRAY . " and received:" . G::EOL;
                $randomizer = new WeightedRandom();
                foreach($crate->getRewards() as $reward) {
                    $perc = $reward[6] / 100;
                    $randomizer->add($reward, $perc);
                }
                $randomizer->setup();
                $reward = $randomizer->generate(1)->current();
                $message .= G::GRAY . " x" . $reward[2] . " " . $reward[3] . G::EOL;
                $pcMgr->giveCrateReward($reward, $player);
                $this->plugin->getServer()->broadcastMessage($message);
                $ev->cancel();
            }
        }
    }

    public function onItemCollect(EntityItemPickupEvent $ev) {
        $player = $ev->getEntity();
        $itemEntity = $ev->getOrigin();
        if($itemEntity instanceof ItemEntity and $player instanceof Player) {
            if(!empty($itemEntity->getOwner()) and $player->getName() !== $itemEntity->getOwner()) {
                if($itemEntity->getDespawnDelay() <= 4800) {
                    $itemEntity->setOwner("");
                }
                $ev->cancel();
            }
        }
    }
}
