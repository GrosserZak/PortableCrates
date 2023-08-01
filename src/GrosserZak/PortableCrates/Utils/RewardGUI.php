<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\Item;

class RewardGUI extends InvMenu {

    /** @var array<int, list<Item>> */
    private static array $contents = [];

    public function __construct(array $contents) {
        self::$contents = $contents;
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenuTypeIds::TYPE_DOUBLE_CHEST));
        parent::setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
            $pageNumber = $transaction->getAction()->getInventory()->getItem(49)->getNamedTag()->getCompoundTag("PortableCrates")?->getCompoundTag("RewardGUI")?->getByte("PageNumber");
            $clickedItem = $transaction->getItemClicked();
            if(($nbt = $clickedItem->getNamedTag()->getCompoundTag("PortableCrates")?->getCompoundTag("RewardGUI")?->getTag("GoToPage")) !== null) {
                $goTo = $nbt->getValue();
                $this->renderItemsPage(($goTo === "Previous" ? $pageNumber-1 : ($goTo === "Next" ? $pageNumber+1 : 0)));
            }
            return $transaction->discard();
        });
        parent::setInventoryCloseListener(function() : void {
            self::renderItemsPage();
        });
        self::renderItemsPage();
    }

    private function renderItemsPage(int $pageNumber = 0) : void {
        $this->getInventory()->setContents(self::$contents[$pageNumber]);
    }

}
