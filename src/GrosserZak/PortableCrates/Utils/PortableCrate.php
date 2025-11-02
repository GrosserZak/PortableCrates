<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use pocketmine\item\Item;

class PortableCrate {

    public function __construct(
        private readonly string $name,
        private readonly string $index,
        private readonly bool   $canBeUpdated,
        private readonly Item   $item,
        private readonly string $id,
        private readonly array  $currentRewards,
        private readonly array  $newRewards
    ) {}

    public function getName() : string {
        return $this->name;
    }

    public function getConfigIndex() : string {
        return $this->index;
    }

    public function getItem() : Item {
        return $this->item;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getCurrentRewards() : array {
        return $this->currentRewards;
    }

    public function getNewRewards() : array {
        return $this->newRewards;
    }

    public function canBeUpdated() : bool {
        return $this->canBeUpdated;
    }

}
