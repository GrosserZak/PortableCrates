<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Commands;

use Exception;
use GrosserZak\PortableCrates\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as G;

class CrateCommand extends Command implements PluginIdentifiableCommand {

    /** @var Main */
    private Main $plugin;

    /** @var Config */
    private Config $crates;

    public function __construct(Main $plugin, Config $crates) {
        parent::__construct("portablecrate", "Portable crate command", "/portablecrate help", ["pcrate"]);
        $this->setPermission("portablecrates.command.give;portablecrates.command.edit");
        $this->plugin = $plugin;
        $this->crates = $crates;
    }

    /**
     * @throws Exception
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        $pfx = $this->plugin->getPCManager()::PREFIX;
        $pcMgr = $this->plugin->getPCManager();
        if(!isset($args[0])) {
            if($sender instanceof ConsoleCommandSender) {
                $sender->sendMessage($pfx . G::RED . " Available commands: \"crate give\" \"crate list\" ");
                return;
            } elseif($sender instanceof Player) {
                $args[0] = "help";
            }
        } elseif(!$sender instanceof Player and $args[0] !== "list" and $args[0] !== "give" and $args[0] !== "reload") {
            $sender->sendMessage($pfx . G::RED . " You must be in-game to use this command!");
            return;
        }
        if(!$this->testPermissionSilent($sender)) {
            $sender->sendMessage(G::RED . "You dont have the permission to run this command!");
            return;
        }
        switch($args[0]) {
            case "help":
                $message = G::GRAY . str_repeat("-", 7) . $pfx . G::GRAY . str_repeat("-", 7) . G::EOL;
                $message .= G::GREEN . "list" . G::GRAY . ": View all the crates" . G::EOL;
                $message .= G::GREEN . "info <name>" . G::GRAY . ": View the information's about a crate" . G::EOL;
                $message .= G::GREEN . "create <name>" . G::GRAY . ": Creates a crate " . G::RED . "(Must hold an item)" . G::EOL;
                $message .= G::GREEN . "delete <name>" . G::GRAY . ": Deletes a crate" . G::EOL;
                $message .= G::GREEN . "add <name> <prob>" . G::GRAY . ": Adds a reward to a crate " . G::RED . "(Must hold an item)" . G::EOL;
                $message .= G::GREEN . "remove <name> <index>" . G::GRAY . ": Removes a reward from a crate by index " . G::EOL
                    . G::RED . "(\"/portablecrate <name> info\" for all reward indexes )" . G::EOL;
                $message .= G::GREEN . "give <name> all|<player> [count]" . G::GRAY . ": Give a player or all the online players a crate" . G::EOL;
                $message .= G::GREEN . "toggle" . G::GRAY . ": Toggles on world give crates" . G::EOL;
                $message .= G::GREEN . "reload" . G::GRAY . ": Reload all config files";
                $sender->sendMessage($message);
                break;
            case "list":
                $message = G::GRAY . str_repeat("-", 7) . $pfx . G::GRAY . str_repeat("-", 7) . G::EOL;
                foreach($pcMgr->getCrates() as $crate) {
                    $message .= G::GRAY . "* Name: " . G:: GREEN . $crate->getName() . G::DARK_GREEN . " [" . $crate->getItem()->getCustomName() . G::RESET . G::DARK_GREEN . "]" . G::EOL;
                }
                $sender->sendMessage($message);
                break;
            case "info":
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate info <name>");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . G::RESET . G::RED . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                $message = G::GRAY . str_repeat("-", 7) . $pfx . G::GRAY . str_repeat("-", 7) . G::EOL;
                $message .= $crate->getItem()->getCustomName() . G::RESET . G::GRAY . " Rewards:" . G::EOL;
                foreach($crate->getRewards() as $index => $reward) {
                    $message .= G::BOLD . G::WHITE . ($index+1) . ". " . G::RESET . G::GRAY . "x" . $reward[2] . " " . $reward[3] . G::RESET . G::DARK_GRAY . " [" . G::GREEN . $reward[6] . "%" . G::DARK_GRAY . "]" . G::EOL;
                }
                $sender->sendMessage($message);
                break;
            case "create":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate create <name>");
                    return;
                }
                if($pcMgr->existsCrate($args[1]) !== null) {
                    $sender->sendMessage($pfx . G::RED . " There's already a crate with name " . $args[1] . "!");
                    return;
                }
                /** @var Player $sender */
                $item = $sender->getInventory()->getItemInHand();
                if($item->isNull()) {
                    $sender->sendMessage($pfx . G::RED . " You must hold an item to create a crate. (It's preferred that the item has custom name and a lore)");
                    return;
                }
                $sender->sendMessage($pfx . G::GREEN . " Crate has been created successfully [" . $item->getName() . G::RESET . G::GREEN . "]");
                $pcMgr->createNewCrate($item, $args[1]);
                break;
            case "delete":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate delete <name>");
                    return;
                }
                $sender->sendMessage($pfx . ($pcMgr->deleteCrateByName($args[1]) ?  G::GREEN . " You've deleted " . $args[1] : G::RED . " There's no crate registered with name " . $args[1] . "!" ));
                break;
            case "add":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate add <name> <prob>");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate add $args[1] <prob>");
                    return;
                }
                if(!is_numeric($args[2]) or ($args[2] <= 0 or $args[2] > 100)) {
                    $sender->sendMessage($pfx . G::RED . " Probability must be a numeric value between 0 and 100 included!");
                    return;
                }
                /** @var Player $sender */
                $item = $sender->getInventory()->getItemInHand();
                $pcMgr->addRewardToCrate($crate, $item, (int)$args[2]);
                $sender->sendMessage($pfx . G::GREEN . " You've added x" . $item->getCount() . " " . $item->getName() . G::RESET . G::GREEN . ", with " . $args[2] . "% chance, to " . $crate->getName() . " Crate");
                break;
            case "remove":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate remove <name> <index>" . G::EOL .
                        "Use \"/portablecrate info <name>\" to see all reward indexes");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate remove $args[1] <index>" . G::EOL .
                        "Use \"/portablecrate info $args[1]\" to see all reward indexes");
                    return;
                }
                if(!is_numeric($args[2]) or $args[2] <= 0) {
                    $sender->sendMessage($pfx . G::RED . " The reward index must a numeric value greater than 0!");
                    return;
                }
                $rewardIndex = (int)$args[2] - 1;
                $sender->sendMessage($pfx . $pcMgr->removeRewardFromCrate($crate, $rewardIndex));
                break;
            case "give":
                if(!$sender->hasPermission("portablecrates.command.give")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1]) or !isset($args[2])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate give <name> all|<player> [count]");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                $count = (int)($args[3] ?? 1);
                if(!is_numeric($count) or $count <= 0) {
                    $sender->sendMessage($pfx . G::RED . " The number of crates to give must be a numeric value greater than 0!");
                    return;
                }
                $crateItem = $crate->getItem();
                $crateItem->setCount($count);
                $giveOnWorld = $this->plugin->getConfig()->get("giveOnWorld");
                if($args[2] !== "all") {
                    $player = $this->plugin->getServer()->getPlayer($args[2]);
                    if(!$player instanceof Player) {
                        $sender->sendMessage($pfx . G::RED . " This player isn't online!");
                        return;
                    }
                    if(!$giveOnWorld or !$sender instanceof Player) {
                        $sender->sendMessage($pfx . G::GRAY . " You gave " . $player->getName() . ": " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                        $pcMgr->giveCrate($player, $crateItem);
                    } else {
                        if($sender->getLevel()->getFolderName() === $player->getLevel()->getFolderName()) {
                            $sender->sendMessage($pfx . G::GRAY . " You gave " . $player->getName() . ": " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                            $pcMgr->giveCrate($player, $crateItem);
                        } else {
                            $sender->sendMessage($pfx . G::RED . " " . $player->getName() . " is not in the world you are on!");
                        }
                    }
                } else {
                    if(!$giveOnWorld or !$sender instanceof Player) {
                        $this->plugin->getServer()->broadcastMessage($pfx . G::YELLOW . " Everyone has been given: " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
                            $pcMgr->giveCrate($p, $crateItem);
                        }
                    } else {
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
                            if($sender->getLevel()->getFolderName() === $p->getLevel()->getFolderName()) {
                                $p->sendMessage($pfx . G::YELLOW . " Everyone has been given: " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                                $pcMgr->giveCrate($p, $crateItem);
                            }
                        }
                    }
                    $sender->sendMessage($pfx . G::GRAY . " You gave everyone: " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                }
                break;
            case "toggle":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                $cfg = $this->plugin->getConfig();
                $value = !((bool)$cfg->get("giveOnWorld"));
                $cfg->set("giveOnWorld", $value);
                $sender->sendMessage($pfx . G::GRAY . " On world give crates has been toggled " . ($value ? G::GREEN . "ON" : G::RED . "OFF"));
                $cfg->save();
                break;
            case "reload":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                $this->crates->reload();
                $this->plugin->getConfig()->reload();
                $sender->sendMessage($pfx . G::GREEN . " All files have been reloaded!");
                break;
            default:
                $sender->sendMessage($pfx . G::RED . " Unknown subcommand! Run \"/portablecrate help\" for a full list of commands");
        }
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}