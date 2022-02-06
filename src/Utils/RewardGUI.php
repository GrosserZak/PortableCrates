<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat as G;

class RewardGUI extends InvMenu {

    /** @var array */
    private array $pages;

    public function __construct(array $rewards) {
        $this->pages = array_chunk($rewards, 45);
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenuTypeIds::TYPE_DOUBLE_CHEST));
        parent::setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
            $page = $transaction->getAction()->getInventory()->getItem(49)->getNamedTag()->getCompoundTag("PortableCrates")?->getCompoundTag("RewardGUI")?->getByte("PageNumber");
            $clickedItem = $transaction->getItemClicked();
            if(($nbt = $clickedItem->getNamedTag()->getCompoundTag("PortableCrates")?->getCompoundTag("RewardGUI")?->getTag("GoToPage")) !== null) {
                $goTo = $nbt->getValue();
                match(true) {
                    $goTo === "Previous" => $this->renderItemsPage($page-1),
                    $goTo === "Next" => $this->renderItemsPage($page+1)
                };
            }
            return $transaction->discard();
        });
        parent::setInventoryCloseListener(function() : void {
            self::renderItemsPage();
        });
        self::renderItemsPage();
    }

    private function renderItemsPage(int $pageNumber = 0) {
        if(!isset($this->pages[$pageNumber])) return;
        $rewards = $this->pages[$pageNumber];
        $rewardsInv = $this->getInventory();
        for($i=0;$i<$rewardsInv->getSize();$i++) {
            if($i>=45) {
                if($i == 46 and isset($this->pages[($pageNumber-1)])) {
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
                            CompoundTag::create()->setByte("PageNumber", $pageNumber)
                        )
                    ));
                    $item->setCustomName(G::RESET . G::WHITE . "Page: " . ($pageNumber+1));
                } elseif($i == 52 and isset($this->pages[($pageNumber+1)])) {
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
            } elseif(!isset($rewards[$i])) {
                $item = VanillaBlocks::BARRIER()->asItem()->setCustomName(G::RESET);
            } else {
                $reward = $rewards[$i];
                $item = ItemFactory::getInstance()->get((int)$reward[0], (int)$reward[1], (int)$reward[2])
                    ->setCustomName(G::RESET . $reward[3])
                    ->setLore(array_merge($reward[4], ["", G::RESET . G::GREEN . $reward[6] . "% probability"]));
            }
            $rewardsInv->setItem($i, $item);
        }
    }

}
