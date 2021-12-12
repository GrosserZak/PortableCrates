# PortableCrates
A PocketMine-MP Crates Plugin that lets players open their crates in place without having them teleport to a warp

## Commands:
| Command | Description | Usage | Alias |
|---|---|---|---|
| `/portablecrate` | Main Command | `/portablecrate` | `/pcrate` |

| SubCommand | Description |
|---|---|
| `help` | View all subcommands |
| `list` | View all existing crates |
| `info <crateName>` | View all reward infos, indexes and probabilities of an existing crate |
| `create <crateName>` | Creates a new crate (You must hold an item that we'll be the crate) |
| `delete <crateName>` | Deletes an existing crate |
| `add <crateName> <probability>` | Adds a reward to an existing crate with a drop rate chance from 1 to 100 (You must hold the item you want to add as reward) |
| `remove <crateName> <rewardIndex>` | Removes a reward by index from an existing crate (Indexes are listed in `list` subcommand) |
| `give <crateName> all:<player> [amount]` | Gives 1 or a defined amount of crates to a defined player or to all online players |
| `toggle` | Toggles GiveOnWorld function |
| `reload` | Reloads config files |

## How to Set up a Crate

### Create a crate

First we'll need an Item that is going to be our crate, in my case im going to give myself a chest with custom name and lore using the following command:
> /give GrosserZak chest 1 {display:{Name:"§r§6§6lRare §fCrate",Lore:["§r", "§r§fContains §6Rare §fItems"]}}

<img src="/images/img1.jpg" alt="Rare Crate"/>

Then we hold our future crate item and use the command `/pcrate create <crateName>` to create our crate.\
In my case im going to use `/pcrate crate rare`.\
Now we can get rid of this item and start adding our rewards to our crate.

### Adding Rewards

To add rewards to our crate we're going to use the `/pcrate add` command.\

For example: \
Lets add 8 EnderPearls with 75% drop rate chance. We'll need to hold 8 EnderPearls and type the following command:
`/pcrate add <crateName> 75` in my case `/pcrate add rare 75`
And there we go! \
We've added 8 EnderPearls with 75% drop rate chance

<img src="/images/img2.jpg" alt="x8 EnderPearls added with 75% drop rate chance to Rare Crate"/> \

We can check its index with the command `/pcrate info <crateName>` in my case `/pcrate info rare` \
<img src="/images/img3.jpg" alt="Rare Crate infos"/>

Now I'm going to add x1 Uncommon Crate with 20% drop rate chance.\
So as before I'm going to hold the uncommon crate and use the command `/pcrate add rare 20`
<img src="/images/img4.jpg" alt="x1 Uncommon Crate added with 20% drop rate chance to Rare Crate"/>

Once we've finished setting up our crate we can give it to ourselves with the command:\
`/pcrate give <crateName> all:<player> [count]` in my case `/pcrate give rare GrosserZak`\
Now we can open our crates

### Removing Rewards

To remove rewards from a crate we'll need to see the reward index that we want to remove from the crate.
We'll need to use the command `/pcrate info <crateName>` to see all reward indexes.\
(Note: The indexes of the rewards are their listing numbers)\
Once we've found the reward index we want to remove we'll need to use the command `/pcrate remove <crateName> <rewardIndex>`

### Delete a crate

Simply just use the command `/pcrate delete <crateName>`

## GiveOnWorld Function
This function ables to give crates on the world where the server administrator runs the `/pcrate give` command.

**Note: This function is not available from console!**

For example:\
The function is enabled:\
[Admin] GrossserZak is in world "FactionLands" with 47 players\
[User] GrossTest is in world "Hub"\
If GrosserZak runs `/pcrate give <crateName> all`, himself and the 47 players, that are in the world he's in, will receive the crate and GrossTest will get nothing.\
Otherwise if the function was disabled all players would receive the crate

## TODO
- [ ] Add **amount** as optional parameter to `add` subcommand to allow giving out more stacks of items
- [ ] Paginate crate rewards GUI

**Note: Any suggestion is appreciated**

---

For any help contact me: \
Telegram: [@zGross](http://telegram.me/zGross) \
Discord: Zak_#0998 \
or just open an issue

