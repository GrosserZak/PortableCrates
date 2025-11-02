# PortableCrates

A PocketMine-MP Crates Plugin that lets players open their crates in place without having them teleport to a warp

## NOTICE

OLDER CONFIG FILES WILL BE DEPRECATED IN VERSION 3.0 \
MAKE SURE TO USE THE VERSION 2.4 OR HIGHER TO UPDATE YOUR CRATES CONFIG FILE

## Commands:
| Command           | Description   | Usage            | Alias     |
|-------------------|---------------|------------------|-----------|
| `/portablecrate`  | Main Command  | `/portablecrate` | `/pcrate` |

| Subcommand  | Description                                                                                                                                                      | Usage                                    | Alias    |
|-------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------|----------|
| `help`      | View all subcommands                                                                                                                                             | `help`                                   |          |
| `list`      | View all existing crates                                                                                                                                         | `list`                                   |          |
| `info`      | View all reward details, indexes and probabilities of an existing crate                                                                                          | `info <crateName>`                       |          |
| `create`    | Creates a new crate (You must be holding the item that will be the crate)                                                                                        | `create <crateName>`                     |          |
| `delete`    | Deletes an existing crate                                                                                                                                        | `delete <crateName>`                     |          |
| `add`       | Adds a reward to an existing crate with a drop rate chance from 1 to 100 (You must be holding the item you want to add as a reward))                             | `add <crateName> <probability>`          |          |
| `remove`    | Removes a reward by index from an existing crate (Indexes are listed in `list` subcommand)                                                                       | `remove <crateName> <rewardIndex>`       |          |
| `unpublish` | Locks the crate, preventing players from getting the updated version. You can edit the crate without it being noticed.                                           | `unpublish <name>`                       | `lock`   |                                                                 
| `"publish`  | Publishes the updated version of the crate. All players can now update their crates."                                                                            | `publish <name>`                         | `unlock` |                                                                           
| `rollback ` | Reverts all edits performed, while the crate was locked, to its original version. You can then resume your edits or proceed with publishing the original version | `revert <name>`                          | `revert` |
| `give`      | Gives 1 or a defined amount of crates to a defined player or to all online players                                                                               | `give <crateName> all:<player> [amount]` |          |
| `toggle`    | Toggles the GiveOnWorld function                                                                                                                                 | `toggle`                                 |          |
| `reload`    | Reloads config files                                                                                                                                             | `relaod`                                 |          |

## How to Set up a Crate

### Create a crate

First, you'll need an item to represent your crate. In this example, I'll give myself a chest with a custom name and lore using the following command:

> /give GrosserZak chest 1 {display:{Name:"§r§6§6§lRare §fCrate",Lore:["§r", "§r§fContains §6Rare §fItems"]}}

<img src="/images/img1.jpg"  alt=""/>

Next, hold the item that will be your new crate and use the command `/portablecrate create <crateName>` to create the crate. \
In my case I'll be executing the command `/portablecrate create rare`

You can now discard the item and begin adding rewards to your newly created crate.

### Adding Rewards

To add rewards to your crate, use the `/portablecrate add <crateName>` command.

#### Example: Adding Ender Pearls

Let's add 8 Ender Pearls with a 75% drop rate chance. 

You can either hold 8 Ender Pearls in your hand or just 1 and define the amount directly using the command. \
In the first case we can execute the command: \
`/portablecrate add <crateName> <chance>` \
The executed command will be `/portablecrate add rare 75`

In the last case we can define the amount of items using the command: \
`/portablecrate add <crateName> <chance> [amount]` \
The final comamnd will be `/portablecrate add rare 75 8`

<img src="/images/img2.jpg" alt=""/>

You can check the reward's index and other details using: \
`/portablecrate info <crateName>` \
In this case: \
`/portablecrate info rare`

<img src="/images/img3.jpg" alt=""/>

Now, I will add x1 Uncommon Crate with a 20% drop rate chance. As before, I will hold the Uncommon Crate item and use the command: \
`/portablecrate add rare 20`

<img src="/images/img4.jpg" alt=""/>

Once you've finished setting up your crate, you can give it to yourself or other players using: \
`/portablecrate give <crateName> all:<player> [count]` \
In this case: \
`/portablecrate give rare GrosserZak 1`

You can now open your crates!

### Removing Rewards

To remove a reward from a crate, you first need to identify its reward index. \
Use the command `/portablecrate info <crateName>` to see all reward indexes.

> (Note: Reward indexes are their sequential listing numbers) 

Once you've found the index of the reward you want to remove, use the command: \
`/portablecrate remove <crateName> <rewardIndex>`

### Delete a crate

To delete an existing crate, simply use the command: \
`/portablecrate delete <crateName>`

## The GiveOnWorld Function

The GiveOnWorld function allows crates to be given only to players in the same world where the server administrator executes the `/portablecrate give command`
Note: This function is not available when running the command from the console!

For example:
**The function is enabled:**
[Admin] GrossserZak is in world "FactionLands" with 47 other players.
[User] GrossTest is in world "Hub".

If GrosserZak runs `/portablecrate give <crateName> all`, only he and the 47 players in "FactionLands" will receive the crate. GrossTest will receive nothing.
If the function were disabled, all online players would receive the crate, regardless of their world.

## How Publish, Unpublish and Rollback work

I added these subcommands because this plugin allows you to edit crates directly in-game. This could lead to a potential issue where players quickly notice and exploit a misconfigured crate.
These subcommands prevent players from accessing the newest changes until you are ready to publish them.

Let's look at an example:

### Using Publish and Unpublish

<img src="/images/img5.png" alt=""/>

In the image above, the output of the `/portablecrate info rare` command includes the crate's publication status on the first line

| Status       | Meaning                                                      | What happens if I edit the crate?                                                                           |
|--------------|--------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| PUBLISHED    | The updates for the crate are currently available to players | All new edits will be instantly available to players                                                        | 
| UNPUBLISHED  | The updates for the crate are hidden from players            | All new edits will **not** be available until you publish them using the `publish` (or `unlock`) subcommand |

In the first case (PUBLISHED), if we add a x1 Wooden Axe with 50% chance, we see it's published and instantly available to players:

<img src="/images/img6.png" alt=""/>

Now, let's look at the best part. \
If we run `/portablecrate unpublish rare` (or `/portablecrate lock rare`), checking `/portablecrate info rare` again will show more details:

<img src="/images/img7.png" alt=""/>

As you can see, the output now states that the changes are not being published. It displays the Published Version (currently available to everyone) and the New Version (which will show all the edits you are about to introduce).

Let's start by removing some rewards. I will remove the rewards with index 2 and 5 using the commands: \
`/portablecrate remove rare 2` \
`/portablecrate remove rare 5`

<img src="/images/img8.png" alt=""/>

For every edit, you'll get a reminder that the updates on the crate are not being published. Let's check the changes now with `/portablecrate info rare`:

<img src="/images/img9.png" alt=""/>

As you can see, the New Version now reflects the removal of those two items.
Now let's add a x1 Diamond Sword with a 10% chance:

<img src="/images/img10.png" alt=""/>

A new entry is now displayed, showing that the New Version will include the Diamond Sword.

Now that we're done editing, let's publish the changes!

<img src="/images/img11.png" alt=""/>

As you can see, the Tall Grass and Warped Planks have been removed from the crate, and a Diamond Sword has been added.

### Using Rollback

Let's unpublish the changes again. Suppose that while I was editing the crate, I accidentally removed the x1 Diamond (which was at index 1), like in this example:

<img src="/images/img12.png" alt=""/>

Instead of manually re-adding the item, I can rollback my changes to the previously published version and start my edits over. \
Use the command: \
`/portablecrate rollback rare`\
or the alias: `/portablecrate revert rare`

<img src="/images/img13.png" alt=""/>

As shown, the changes have been reverted to the current published version.

## To-Do List

- [ ] Add **amount** as an optional parameter to the `add` subcommand to allow for adding more than one stack of an item
- [ ] Add the option to give the UNPUBLISHED version of the crate to admin for test purposes
- [ ] Add optional crate opening animation

**Note: Any suggestion is appreciated**

---

For any help, you can contact me via: \
Telegram: [@zGross](http://telegram.me/zGross) \
Discord: grosserzak \
Or, simply open an issue on the project page

