<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Commands;

use Exception;
use GrosserZak\PortableCrates\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as G;

class CrateCommand extends Command implements PluginOwned {

    public function __construct(
        private readonly Main $plugin,
        private readonly Config $crates
    ) {
        parent::__construct("portablecrate", "Portable crate command", "/portablecrate help", ["pcrate"]);
        $this->setPermission("portablecrates.command.give;portablecrates.command.edit");
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
                $message .= G::GREEN . "add <name> <prob> [amount]" . G::GRAY . ": Adds a reward to a crate. " . G::RED . "(Must hold an item)" . G::EOL .
                    G::GOLD . "[NOTICE]" . G::GRAY . " If you don't specify the amount, it will be counted the amount of the item you're holding" . G::EOL;
                $message .= G::GREEN . "remove <name> <index>" . G::GRAY . ": Removes a reward from a crate by index " . G::EOL
                    . G::RED . "(\"/portablecrate <name> info\" for all reward indexes )" . G::EOL;
                $message .= G::GREEN . "unpublish <name>" . G::GRAY . ": Locks the crate from being updated for players. You can edit the crate without being noticed." . G::EOL;
                $message .= G::GREEN . "publish <name>" . G::GRAY . ": Publish the updated version of the crate. All players can update their crates." . G::EOL;
                $message .= G::GREEN . "rollback <name>" . G::GRAY . ": Reverts all the edits performed, while the crate was locked, to its current version. You can begin with your edits or proceed with publishing it as it was originally" . G::EOL;
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
                $message .= G::WHITE . "The updates of this crate are currently " . ($crate->canBeUpdated() ? G::GREEN . "PUBLISHED" : G::RED . "UNPUBLISHED") . G::EOL;
                $message .= $crate->getItem()->getCustomName() . G::RESET . G::GRAY . " Rewards:" . G::EOL;
                if (!$crate->canBeUpdated()) {
                    $message .= G::WHITE . "Published version (What players can see currently):" . G::EOL;
                    foreach($crate->getCurrentRewards() as $index => $reward) {
                        $message = $this->buildRewardMessage($index, $reward, $message);
                    }
                    $message .= G::WHITE . "New version (Currently being edited):" . G::EOL;
                    $newCurrentRewardsTemp = $crate->getNewRewards();
                    foreach($crate->getCurrentRewards() as $reward) {
                        if(in_array($reward, $newCurrentRewardsTemp)) {
                            $locatedIndex = array_search($reward, $newCurrentRewardsTemp);
                            $message = $this->buildRewardMessage(null, $reward, $message, G::GRAY . G::BOLD . "UNMODIFIED");
                            array_splice($newCurrentRewardsTemp, $locatedIndex, 1);
                        } else {
                            $message = $this->buildRewardMessage(null, $reward, $message, G::RED . G::BOLD . "REMOVED");
                        }
                    }
                    foreach($newCurrentRewardsTemp as $reward) {
                        $message = $this->buildRewardMessage(null, $reward, $message, G::GREEN . G::BOLD . "ADDED");
                    }
                } else {
                    foreach($crate->getCurrentRewards() as $index => $reward) {
                        $message = $this->buildRewardMessage($index, $reward, $message);
                    }
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
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate add <name> <prob> [amount]");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate add $args[1] <prob> [amount]");
                    return;
                }
                if(!is_numeric($args[2]) or ($args[2] <= 0 or $args[2] > 100)) {
                    $sender->sendMessage($pfx . G::RED . " Probability must be a numeric value between 0 and 100 included!");
                    return;
                }
                $item = $sender->getInventory()->getItemInHand();
                if(isset($args[3])) {
                    if(!is_numeric($args[3]) or $args[3] <= 0) {
                        $sender->sendMessage($pfx . G::RED . " Amount must be a numeric number greater than 0!");
                        return;
                    }
                    $count = (int)$args[3];
                } else {
                    $count = $item->getCount();
                }
                /** @var Player $sender */
                $pcMgr->addRewardToCrate($crate, $item, (int)$args[2], $count);
                $sender->sendMessage($pfx . G::GREEN . " You've added x" . $count . " " . $item->getName() . G::RESET . G::GREEN . ", with " . $args[2] . "% chance, to " . $crate->getName() . " Crate");
                if(!$crate->canBeUpdated()) {
                    $sender->sendMessage($pfx . G::RED . " NOTE: Remember to publish the new version once you've finished editing the crate with the command " . G::GOLD . "/portablecrate publish " . $crate->getName() . G::EOL
                    . G::RED . "You can view the current and the newest version using " . G::GOLD . "/portablecrate info " . $crate->getName());
                }
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
                if(!$crate->canBeUpdated()) {
                    $sender->sendMessage($pfx . G::RED . " NOTE: Remember to publish the new version once you've finished editing the crate with the command " . G::GOLD . "/portablecrate publish " . $crate->getName() . G::EOL
                        . G::RED . "You can view the current and the newest version using " . G::GOLD . "/portablecrate info " . $crate->getName());
                }
                break;
            case "unpublish":
            case "lock":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate unpublish|lock <name>");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if(!$crate->canBeUpdated()) {
                    $sender->sendMessage($pfx . G::GREEN . " The updates on the crate " . $crate->getItem()->getName() . G::RESET . G::GREEN . " are already locked!");
                } else {
                    $pcMgr->lockUpdates($crate);
                    $sender->sendMessage($pfx . G::GREEN . " The updates of the crate " . $crate->getItem()->getName() . G::RESET . G::GREEN . " have been locked!" . G::EOL . "Now you can edit the crate without players noticing");
                }
                break;
            case "publish":
            case "unlock":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate publish|unlock <name>");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if($crate->canBeUpdated()) {
                    $sender->sendMessage($pfx . G::GREEN . " The updated version of the crate " . $crate->getItem()->getName() . G::RESET . G::GREEN . " is already published!");
                } else {
                    $pcMgr->publishUpdates($crate);
                    $sender->sendMessage($pfx . G::GREEN . " The updates of the crate " . $crate->getItem()->getName() . G::RESET . G::GREEN . " have been published!" . G::EOL . "Now players can update their crates.");
                }
                break;
            case "rollback":
            case "revert":
                if(!$sender->hasPermission("portablecrates.command.edit")) {
                    $sender->sendMessage($pfx . G::RED . " Insufficient Permissions");
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($pfx . G::RED . " Usage: /portablecrate rollback|revert <name>");
                    return;
                }
                if(($crate = $pcMgr->existsCrate($args[1])) === null) {
                    $sender->sendMessage($pfx . G::RED . " Couldn't find crate with name " . $args[1] . "! Run \"/portablecrate list\" to view all the crates");
                    return;
                }
                if($crate->canBeUpdated()) {
                    $sender->sendMessage($pfx . G::RED . " Cannot rollback the " . $crate->getItem()->getName() . G::RESET . G::RED . " since its not being edited in lock mode!");
                } else {
                    $pcMgr->rollbackUpdates($crate);
                    $sender->sendMessage($pfx . G::GREEN . " The new version of the crate " . $crate->getItem()->getName() . G::RESET . G::GREEN . " has been reverted to its current version!" . G::EOL . "You can now either publish the crate as it was or proceed with new edits.");
                }
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
                    $player = $this->plugin->getServer()->getPlayerExact($args[2]);
                    if(!$player instanceof Player) {
                        $sender->sendMessage($pfx . G::RED . " This player isn't online!");
                        return;
                    }
                    if(!$giveOnWorld or !$sender instanceof Player) {
                        $sender->sendMessage($pfx . G::GRAY . " You gave " . $player->getName() . ": " . G::WHITE . "x" . $crateItem->getCount() . " " . $crateItem->getCustomName());
                        $pcMgr->giveCrate($player, $crateItem);
                    } else {
                        if($sender->getWorld()->getFolderName() === $player->getWorld()->getFolderName()) {
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
                            if($sender->getWorld()->getFolderName() === $p->getWorld()->getFolderName()) {
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
                $value = !($cfg->get("giveOnWorld"));
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

    public function getOwningPlugin() : Plugin {
        return $this->plugin;
    }

    public function buildRewardMessage(?int $index, mixed $reward, string $message, string $changeStatus = "") : string {
        $message .= (!is_null($index) ? G::BOLD . G::WHITE . ($index + 1) . ". " . G::RESET : G::RESET) . G::GRAY . "x" . $reward[1] . " " . $reward[2] . G::RESET . G::DARK_GRAY . " [" . G::GREEN . $reward[5] . "%" . G::DARK_GRAY . "]" . (empty($changeStatus) ? "" : G::DARK_GRAY . " | " . $changeStatus) . G::EOL;
        return $message;
    }
}
