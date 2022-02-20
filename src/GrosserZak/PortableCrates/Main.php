<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates;

use GrosserZak\PortableCrates\Commands\CrateCommand;
use GrosserZak\PortableCrates\Listener\EventListener;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var PCManager */
    private PCManager $pcManager;

    public function onEnable() : void{
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        $this->saveDefaultConfig();
        $this->saveResource("crates.yml");
        $cratesCfg = new Config($this->getDataFolder() . "crates.yml");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new CrateCommand($this, $cratesCfg));
        $this->pcManager = new PCManager($cratesCfg);
    }

    public function getPCManager() : PCManager {
        return $this->pcManager;
    }
}
