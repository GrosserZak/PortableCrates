<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use pocketmine\item\Item;

class PortableCrate {

    public function __construct(
        private string $name,
        private int $index,
        private Item $item,
        private string $id,
        private array $rewards
    ) {}

    public function getName() : string {
        return $this->name;
    }

    public function getConfigIndex() : int {
        return $this->index;
    }

    public function getItem() : Item{
        return $this->item;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getRewards() : array {
        return $this->rewards;
    }

}
