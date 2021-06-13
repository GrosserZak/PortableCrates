<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Listener;

use GrosserZak\PortableCrates\Main;
use GrosserZak\PortableCrates\Utils\WeightedRandom;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat as G;

class EventListener implements Listener {

    /** @var Main */
    private Main $plugin;

    /** @var array */
    private array $crateCooldown;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->crateCooldown = [];
    }

    public function onInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if($item->hasCompoundTag()) {
            if($item->getNamedTag()->hasTag("PortableCrate") and $item->getNamedTag()->hasTag("PortableCrateID")) {
                $pcMgr = $this->plugin->getPCManager();
                $crate = $pcMgr->existsCrate($item->getNamedTag()->getString("PortableCrate"));
                if($player->isSneaking()) {
                    if(!$pcMgr->openCrateRewardsGUI($player, $crate)) {
                        $player->sendPopup(G::RED . "Cannot show crate rewards! Contact an administrator");
                    }
                    $ev->setCancelled();
                    return;
                }
                if(time() < ($this->crateCooldown[$player->getLowerCaseName()] ?? -1)) {
                    $player->sendMessage(G::RED . "You have to wait before opening another crate!");
                    $ev->setCancelled();
                    return;
                }
                $crateItem = $crate->getItem();
                if($item->getNamedTag()->getString("PortableCrateID") !== $crate->getId()) {
                    $player->sendMessage(G::GREEN . "The version of your crate was old! Now it's updated, Enjoy!");
                    $pcMgr->giveCrate($player, $player, $crateItem);
                    return;
                }
                $this->crateCooldown[$player->getLowerCaseName()] = time() + 3;
                $item->pop();
                $player->getInventory()->setItemInHand($item);
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
                $ev->setCancelled();
            }
        }
    }
}