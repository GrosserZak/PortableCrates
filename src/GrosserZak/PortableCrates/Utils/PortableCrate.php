<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat as G;

class PortableCrate {

    /** @var string */
    private string $name;

    /** @var int */
    private int $index;

    /** @var Item */
    private Item $item;

    /** @var string */
    private string $id;

    /** @var array */
    private array $rewards;

    /** @var InvMenu|null */
    private ?InvMenu $GUI;

    public function __construct(string $name, int $index, Item $item, string $id, array $rewards) {
        $this->name = $name;
        $this->index = $index;
        $this->item = $item;
        $this->id = $id;
        $this->rewards = $rewards;
        $this->GUI = $this->initRewardGUI();
    }

    private function initRewardGUI() : ?InvMenu {
        $rewards = $this->rewards;
        $nRewards = count($rewards);
        if($nRewards > 54) {
            return null;
        }
        $menu = InvMenu::create(($nRewards < 27 ? InvMenu::TYPE_CHEST : InvMenu::TYPE_DOUBLE_CHEST));
        $menu->setListener(InvMenu::readonly());
        $rewardsInv = $menu->getInventory();
        for($i=0;$i<$rewardsInv->getSize();$i++) {
            if($i >= $rewardsInv->getSize()) break;
            if(!isset($rewards[$i])) {
                $item = ItemFactory::get(Item::STAINED_GLASS_PANE, 15)->setCustomName(G::RESET);
            } else {
                $reward = $rewards[$i];
                $item = ItemFactory::get((int)$reward[0], (int)$reward[1], (int)$reward[2])
                    ->setCustomName(G::RESET . $reward[3])
                    ->setLore(array_merge($reward[4], ["", G::RESET . G::GREEN . $reward[6] . "% probability"]));
            }
            $rewardsInv->setItem($i, $item);
        }
        return $menu;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getConfigIndex() : int {
        return $this->index;
    }

    public function getItem() : Item{
        return $this->item;
    }

    public function getId() : string{
        return $this->id;
    }

    public function getRewards() : array{
        return $this->rewards;
    }

    public function getRewardGUI() : ?InvMenu {
        return $this->GUI;
    }

}