<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use pocketmine\item\Item;

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

    public function __construct(string $name, int $index, Item $item, string $id, array $rewards) {
        $this->name = $name;
        $this->index = $index;
        $this->item = $item;
        $this->id = $id;
        $this->rewards = $rewards;
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

    public function getId() : string {
        return $this->id;
    }

    public function getRewards() : array {
        return $this->rewards;
    }

}
