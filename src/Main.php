<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates;

use GrosserZak\PortableCrates\Commands\CrateCommand;
use GrosserZak\PortableCrates\Listener\EventListener;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var Config */
    private Config $crates;

    /** @var PCManager */
    private PCManager $pcManager;

    public function onEnable() : void{
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        $this->saveDefaultConfig();
        $this->saveResource("crates.yml");
        $this->crates = new Config($this->getDataFolder() . "crates.yml");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new CrateCommand($this, $this->crates));
        $this->pcManager = new PCManager($this);
    }

    public function getCratesCfg() : Config {
        return $this->crates;
    }

    public function getPCManager() : PCManager {
        return $this->pcManager;
    }
}
