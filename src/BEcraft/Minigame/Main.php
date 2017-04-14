<?php

namespace BEcraft\Minigame;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\tile\Sign;
use pocketmine\event\entity\EntityLevelChangeEvent;
use BEcraft\Minigame\task\GameTask;
use BEcraft\Minigame\task\WinParticle;
use BEcraft\Minigame\task\SignTask;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat as T;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\Level;

class Main extends PluginBase implements Listener{

	public $creator = array();
	
	public $arena = array();
	
	public $playing = array();
	
	public $game = array();

	public $move = array();
	
	public $request = array();
	
	public $sent = array();
	
	public $editor = array();
	
	public $games = array();
	
	public function onLoad(){
	$this->getLogger()->info(T::GOLD."Loading");
	}
	
	public function onDisable(){
	$this->getLogger()->info(T::RED."Disabled...");
	}
	
	public function onEnable(){
	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->createConfig();
	$this->loadArenas();
	$this->updateStatus();
	$this->updateSign();
	$this->setArenas();
	$this->getLogger()->info(T::GREEN."Enabled!");
	}
	
	public function createConfig(){
	@mkdir($this->getDataFolder());
	@mkdir($this->getDataFolder()."Arenas/");
	$this->messages = new Config($this->getDataFolder()."Messages.yml", Config::YAML, [
	"killed_by" => "§6{victim} §7has been killed by §a{killer}",
	"create_sign" => "§eYou created a game sign for arena §a{arena}!",
	"broken_sign" => "§cThere is not any arena with that name!",
	"already_started" => "§cGame already started!",
	"already_playing" => "§cYou are already playing!",
	"broadcast_join" => "§6{player} §ejoined to fight at §a{arena}",
	"joined_to_arena" => "§eYou joined to §a{arena} §earena. §c{players}/2",
	"joined_new_player" => "§e{player} §7joined to the match!",
	"creating_new_arena" => "§aStart setting arena name with /practice name <name>",
	"player_creating_arena" => "§cthere is a player creating an arena!",
	"need_creator" => "§cYou need to be in creator mode!",
	"new_arena_name" => "§aArena {arena} created!",
	"pos1" => "§6Continue with §e/practice pos1",
	"arena_name_exists" => "§cThere is a game with that name... please enter other name!",
	"no_numbers" => "§cPlease use letters instance of numers!",
	"pos1_done" => "§eFirst position set correctly!",
	"pos1_needed" => "§cYou need to set arena name first!",
	"pos2" => "§6Continue with §e/practice pos2",
	"pos2_done" => "§ePositions added correctly!",
	"pos2_needed" => "§cYou need to set the second position first!",
	"armor" => "§6Continue with §e/practice armor",
	"armor_done" => "§eArmor added correctly!",
	"armor_need" => "§cYou need to set armor first!",
	"name_missing" => "§cYou need to set a name first!",
	"items" => "§6Continue with §e/practice items",
	"arena_done" => "§aGame completed!",
	"practice_make" => "§a/practice make §7[§6Create a new arena!§7]",
	"practice_arena" => "§a/arena §7[§6See arena commands§7]",
	"practice_editor" => "§a/practice editor §7[§6Join to editor mode!§7]",
	"practice_set_items" => "§a/practice setitems <arena> §7[§6Set new items for any arena (you have to be in editor mode!)§7]",
	"practice_set_armor" => "§a/practice setarmor <arena> §7[§6Set new armor to any arena (you have to be in creator mode!)§7]",
	"practice_arenatype" => "§a/practice <onevone | kohi> <arena> §7[§6Set any arena type (you need to be in creator mode!)§7]",
	"practice_done" => "§a/practice done §7[§6Leave from editor mode!§7]",
	"enable_editor_mode" => "§aYou are in editor mode now!",
	"set_onevone" => "§aChanged type of game to 1vs1 correctly for arena §6{arena}",
	"already_onevone" => "§eThis arena already is 1vs1",
	"game_no_exists" => "§cSorry this game not exists!",
	"need_editor" => "§cYou need to be in editor mode to use this command",
	"set_kohi" => "§aChanged type of game to potpvp correctly for arena {arena}",
	"already_kohi" => "§eThis arena already is type potpvp",
	"set_new_armor" => "§aChanged armor correctly for arena {arena}",
	"set_new_items" => "§aChanged items correctly for arena {arena}",
	"leave_editor" => "§cYou left from creator mode!",
	"you_are_no_playing" => "§cYou are not playing!",
	"you_left_from_game" => "§eYou left from arena!",
	"sent_request" => "§5You sent a duel request to §c{player}",
	"new_request" => "§a{sender} §6sent you a duel request, type /arena accept §a{sender} to accept it!",
	"requested" => "§cThis player sent a request to other player!",
	"already_sent_request" => "§cYou already sent a request to other player, if you want cancel it use /arena!",
	"cannot_duel" => "§cYou have to decline or accept your current request for duel other player!",
	"duel_process" => "§cThis player has a request in process!",
	"no_duel_youselft" => "§cYou cant send request to yourselft xD",
	"player_offline" => "§cSorry this player is not online!",
	"no_free_arenas" => "§cThere is not any free arena to fight!",
	"not_request" => "§cSorry this player is not in your request list!",
	"none_request" => "§cYou dont have any duel request!",
	"decline" => "§eYou declined §a{player} §erequest!",
	"declined" => "§a{sender} §cdeclined your duel request!",
	"no_request_sent" => "§cSorry this player didnt send you any request!",
	"cancelled_request" => "§aYou cancelled your duel request!",
	"cancel" => "§c{sender} §6cancelled the duel request!",
	"no_duel_request_sent" => "§cYou didnt send any duel request",
	"waiting_popup" => "§eWaiting for your oponent: §6{players} | 2",
	"game_started" => "§aGame started, good luck!",
	"win_message" => "§6{player} §ewon a duel in arena: §a{arena}",
	"time_over" => "§7Time is over, good luck at next!",
	"no_win" => "§cNobody won in arena: §e{arena}",
	]);
	$this->messages->save();
	}
	
	/*
	 * =======================
	 * ARENAS SIGNS TASK
	 *=======================
	 */
	public function updateSign(){
	$this->getServer()->getScheduler()->scheduleRepeatingTask(new SignTask($this), 30)->getTaskId();
	}
	
	/*
	 * =======================
	 * UPDATE ARENA STATUS
	 *=======================
	 */
	public function updateStatus(){
	if(!empty($this->getDataFolder()."Arenas/")){
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $files){
	if($files !== ".." and $files !== "."){
	$name = str_replace(".yml", "", $files);
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$status = $arena->get("Status");
	if($status == "running"){
	$arena->set("Status", "waiting");
	$arena->save();
	}
	if(empty($arena->get("Type"))){
	$arena->set("Type", "onevone");
	$arena->save();
	}
    }
	}
	}
	}
	
	/*
	 * =======================
	 * ARENA TASK
	 *=======================
	 */
	public function newTask($game){
	$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameTask($this, $game), 30)->getTaskId();
	}
	
	/*
	 * =======================
	 * WIN PARTICLE TASK
	 *=======================
	 */
	public function WinTask(Player $player){
	$this->getServer()->getScheduler()->scheduleRepeatingTask(new WinParticle($this, $player), 10)->getTaskId();
	}
	
	/*
	 * =======================
	 * LOAD ALL ARENAS WORLDS
	 *=======================
	 */
	public function loadArenas(){
	if(!empty($this->getDataFolder()."Arenas/")){
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $files){
	if($files !== ".." and $files !== "."){
	$name = str_replace(".yml", "", $files);
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	if(empty($arena->get("Level"))) return;
	if(!$this->getServer()->getLevelByName($arena->get("Level")) instanceof Level) return;
	$level = $arena->get("Level");
    $world = $this->getServer()->getLevelByName($level);
	$this->getServer()->loadLevel($level);
	$world->setTime(0);
	$world->stopTime();
	$this->getLogger()->notice(T::GOLD."\nLoading arenas: \n".T::GREEN.$level);
	}
	}
	}
	}
	
	/*
	 * =======================
	 * GET COUNT OF ALL PLAYERS IN GAME
	 *=======================
	 */
	public function getCount(){
	return count($this->playing);
	}
	
	/*
	 * =======================
	 * GET COUNT OF PLAYERS IN ARENA
	 *=======================
	 */
	public function gamesCount($game){
	return count($this->games[$game]);
	}
	
	/*
	 * =======================
	 * ADD A PLAYER TO GAME
	 *=======================
	 */
	public function addPlayer(Player $player, $game){
	$this->games[$game][] = $player;
	}
	
	/*
	 * =======================
	 * REMOVE A PLAYER FROM ARENA
	 *=======================
	 */
	public function deletePlayer(Player $player){
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $files){
	if($files !== ".." and $files !== "."){
	$game = str_replace(".yml", "", $files);
	$search = array_search($player, $this->games[$game]);
	unset($this->games[$game][$search]);
	}
	}
	}
	
	/*
	 * =======================
	 * SET ALL ARENAS AVAIABLE
	 *=======================
	 */
	public function setArenas(){
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $files){
	if($files !== ".." and $files !== "."){
	$game = str_replace(".yml", "", $files);
	$this->games[$game] = array();
	}
	}
	}
	
	/*
	 * =======================
	 * ADD A NEW ARENA
	 *=======================
	 */
	public function addArena($game){
	$this->games[$game] = array();
	}
	
	/*
	 * =======================
	 * REMOVE PLAYER WHEN LEAVE
	 *=======================
	 */
	public function onQuit(PlayerQuitEvent $e){
	$p = $e->getPlayer();
	$this->deletePlayer($p);
	if(in_array($p->getName(), $this->playing)){
	unset($this->playing[$p->getName()]);
	}
	if(in_array($p->getName(), $this->creator)){
	unset($this->creator[$p->getName()]);
	}
	if(isset($this->request[$p->getName()])){
	$player = $p->getServer()->getPlayer($this->request[$p->getName()]);
	unset($this->sent[$player->getName()]);
	unset($this->request[$p->getName()]);
	}
	if(isset($this->sent[$p->getName()])){
	$player = $p->getServer()->getPlayer($this->sent[$p->getName()]);
	unset($this->request[$player->getName()]);
	unset($this->sent[$p->getName()]);
	}
	if(in_array($p->getName(), $this->editor)){
	unset($this->editor[$p->getName()]);
	}
	}
	
	/*
	 * =======================
	 * PLAYER DEATH EVENT
	 *=======================
	 */
	public function onDeath(PlayerDeathEvent $e){
	$p = $e->getPlayer();
	$cause = $p->getLastDamageCause();
	if($cause instanceof EntityDamageByEntityEvent){
	$killer = $cause->getDamager();
	$victim = $cause->getEntity();
	if($killer instanceof Player){
	if(in_array($victim->getName(), $this->playing)){
	if(in_array($killer->getName(), $this->playing)){
	$e->setDeathMessage("");
	$e->setDrops([]);
	$message = $this->messages->get("killed_by");
	$message = str_replace(["{victim}", "{killer}"], [$victim->getName(), $killer->getName()], $message);
	Server::getInstance()->broadcastMessage($message);
	$victim->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	unset($this->playing[$victim->getName()]);
	$this->deletePlayer($victim);
	}
	}
	}
	}
	}
	
	/*
	 * =======================
	 * CHECK IF ANY ARENA EXISTS
	 *=======================
	 */
	public function arenaExists($name){
	if(file_exists($this->getDataFolder()."Arenas/".$name.".yml")){
	return true;
	}else{
	return false;
	}
	}
	
	/*
	 * =======================
	 * PLAYER JOIN EVENT
	 *=======================
	 */
	public function onJoinEvent(PlayerJoinEvent $e){
	$pl = $e->getPlayer();
    $pl->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	$pl->getInventory()->clearAll();
	$pl->removeAllEffects();
	}
	
	/*
	 * =======================
	 * CREATE A SIGN FOR ANY ARENA
	 *=======================
	 */
	public function signChange(SignChangeEvent $e){
	$p = $e->getPlayer();
	if($p->isOp()){
	if($e->getLine(0) == "practice"){
	$name = strtolower($e->getLine(1));
	if($this->arenaExists($name)){
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$arena_name = $arena->get("Name");
	$arena_level = $arena->get("Level");
	$prefix = T::GRAY."[".T::YELLOW."Practice".T::GRAY."]".T::RESET;
	$e->setLine(0, $prefix);
	$e->setLine(1, $arena_name);
	$e->setLine(2, $arena_level);
	$e->setLine(3, "");
	$message = $this->messages->get("create_sign");
	$message = str_replace("{arena}", $arena_name, $message);
	$p->sendMessage($message);
	$p->getLevel()->addSound(new AnvilUseSound(new Vector3($p->x, $p->y, $p->z)));
	}else{
	$p->sendMessage($this->messages->get("broken_sign"));
	$e->setLine(0, T::RED."BROKEN");
	$e->setLine(1, T::RED."BROKEN");
	$e->setLine(2, T::RED."BROKEN");
	$e->setLine(3, T::RED."BROKEN");
	}
	}
	}
	}
	
	/*
	 * =======================
	 * JOIN TO ARENA BY SIGN
	 *=======================
	 */
	public function onInteract(PlayerInteractEvent $e){
	$p = $e->getPlayer();
	$b = $e->getBlock();
	$sign = $p->getLevel()->getTile($b);
	if($sign instanceof Sign){
	$line = $sign->getText();
	$prefix = T::GRAY."[".T::YELLOW."Practice".T::GRAY."]".T::RESET;
	if($line[0] == $prefix){
	$game = $line[1];
	if($this->arenaExists($game)){
	$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$name = $arena->get("Name");
	$level = $arena->get("Level");
	if(!in_array($p->getName(), $this->playing)){
	if($this->gamesCount($game) == 0){
	$this->newTask($game);
	$pone = $arena->get("PosOne");
	$x = $pone[0];
	$z = $pone[2];
	$ar = $this->getServer()->getLevelByName($level);
	$y = $pone[1];
	$p->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($p, $game);
	$this->setKit($p, $game);
	$p->getLevel()->addSound(new EndermanTeleportSound(new Vector3($p->x, $p->y, $p->z)));
	}else
	if($this->gamesCount($game) == 1){
	$ptwo = $arena->get("PosTwo");
	$x = $ptwo[0];
	$z = $ptwo[2];
	$ar = $this->getServer()->getLevelByName($level);
	$y = $ptwo[1];
	$p->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($p, $game);
	$this->setKit($p, $game);
	$p->getLevel()->addSound(new EndermanTeleportSound(new Vector3($p->x, $p->y, $p->z)));
	}else
    if($this->gamesCount($game) == 2){
	$p->sendMessage($this->messages->get("already_started"));
	}
	}else{$p->sendMessage($this->messages->get("already_playing"));}
	}
	}
	}
	}
	
	/*
	 * =======================
	 * IF PLAYER CHANGE OF LEVEL WHILE IS IN GAME
	 *=======================
	 */
	public function LevelChange(EntityLevelChangeEvent $e){
	$p = $e->getEntity();
	if($p instanceof Player){
	if((in_array($p->getName(), $this->move)) || (in_array($p->getName(), $this->playing))){
	if($p->getLevel() !== $this->getServer()->getDefaultLevel()){
	unset($this->move[$p->getName()]);
	unset($this->playing[$p->getName()]);
	$p->removeAllEffects();
	$this->deletePlayer($p);
	}
	}
	}
	}
	
	/*
	 * =======================
	 * SET POTPVP KIT TO PLAYER
	 *=======================
	 */
	public function set_kohi(Player $player){
	$i = $player->getInventory();
	$i->setContents([Item::get(Item::DIAMOND_SWORD, 0, 1), Item::get(Item::GOLDEN_CARROT, 0, 64), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1), Item::get(438, 22, 1)]);
	$i->setArmorContents([Item::get(Item::DIAMOND_HELMET, 0, 1), Item::get(Item::DIAMOND_CHESTPLATE, 0, 1), Item::get(Item::DIAMOND_LEGGINGS, 0, 1), Item::get(Item::DIAMOND_BOOTS, 0, 1)]);
	}
	
	/*
	 * =======================
	 * SET CUSTOM KIT TO PLAYER
	 *=======================
	 */
	public function setKit(Player $player, $game){
	$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$name = $arena->get("Name");
	$level = $arena->get("Level");
	$status = $arena->get("Status");
	$type = $arena->get("Type");
	$this->playing[$player->getName()] = $player->getName();
	$this->move[$player->getName()] = $player->getName();
	$player->setGamemode(0);
	$player->setFlying(false);
	$blind = Effect::getEffect(15);
	$blind->setDuration(9999);
	$blind->setAmplifier(10);
	$blind->setVisible(false);
	$player->addEffect($blind);
	$player->setHealth(20);
	$player->getInventory()->clearAll();
	if($type !== "kohi"){
	$armor = $arena->get("Armor");
	$h = $armor["helmet"];
	$c = $armor["chest"];
	$l = $armor["leggings"];
	$b = $armor["boots"];
	$player->getInventory()->setHelmet(Item::get($h));
	$player->getInventory()->setChestPlate(Item::get($c));
	$player->getInventory()->setLeggings(Item::get($l));
	$player->getInventory()->setBoots(Item::get($b));
	$in = $arena->get("Items");
	if(empty($in)) return;
	foreach($in as $slot => $item){
	$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2]));
	}
	}else{
	$this->set_kohi($player);
	}
	$broad = $this->messages->get("broadcast_join");
	$broad = str_replace(["{player}", "{arena}"], [$player->getName(), $game], $broad);
	Server::getInstance()->broadcastMessage($broad);
	$message = $this->messages->get("joined_to_arena");
	$message = str_replace(["{arena}", "{players}"], [$game, $this->gamesCount($game)], $message);
	$player->sendMessage($message);
	foreach($this->games[$game] as $jugador){
	$new = $this->messages->get("joined_new_player");
	$new = str_replace("{player}", $player->getName(), $new);
	$jugador->sendMessage($new);
	}
	}
	
	/*
	 * =======================
	 * CANCEL EVENT IN GAME
	 *=======================
	 */
	public function onBreak(BlockBreakEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->playing)){
	$e->setCancelled(true);
	}
	}
	
	/*
	 * =======================
	 * CANCEL MOVEMENT 
	 *=======================
	 */
	public function onMove(PlayerMoveEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->move)){
	$to = clone $e->getFrom();
	$to->pitch = $e->getTo()->pitch;
	$e->setTo($to);
	}
	}
	
	/*
	 * =======================
	 * CANCEL EVENT IN GAME
	 *=======================
	 */
	public function onPlace(BlockPlaceEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->playing)){
	$e->setCancelled(true);
	}
	}
	
	/*
	 * =======================
	 * COMMANDS
	 *=======================
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
	switch($command->getName()){
	case "practice":
	if(!$sender instanceof Player) return;
	if($sender->isOp()){
	if(isset($args[0])){
	/*
	 * =======================
	 * MAKE COMMAND
	 *=======================
	 */
	if($args[0] == "make"){
	if(!in_array($sender->getName(), $this->creator)){
	if(count($this->creator) == 0){
	$this->creator[$sender->getName()] = $sender->getName();
	$sender->sendMessage($this->messages->get("creating_new_arena"));
	}else{$sender->sendMessage($this->messages->get("player_creating_arena"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
	}else if($args[0] == "name"){
	if(in_array($sender->getName(), $this->creator)){
	$name = strtolower($args[1]);
	if(!is_numeric($name)){
	if(!file_exists($this->getDataFolder()."Arenas/".$name.".yml")){
	/*
	 * =======================
	 * GIVING ARENA NAME
	 *=======================
	 */
	$this->arena["Name"] = $name;
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML, [
	"Name" => $name,
	"Level" =>  $sender->getLevel()->getFolderName(),
	"PosOne" => "not set",
	"PosTwo" => "no set",
	"Armor" => "no set",
	"Items" => "no set",
	"Status" => "not set",
	"Type" => "onevone",
	 ]);
	$arena->save();
	$this->addArena($name);
	$message = $this->messages->get("new_arena_name");
	$message = str_replace("{arena}", $name, $message);
	$sender->sendMessage($message);
	$sender->sendMessage($this->messages->get("pos1"));
	}else{$sender->sendMessage($this->messages->get("arena_name_exists"));}
	}else{$sender->sendMessage($this->messages->get("no_numbers"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
	}else if($args[0] == "pos1"){
	if(in_array($sender->getName(), $this->creator)){
	if(isset($this->arena["Name"])){
	/*
	 * =======================
	 * SETTING FIRST POSITION 
	 *=======================
	 */
	$this->arena["pos1"] = "pos1";
	$name = $this->arena["Name"];
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$arena->set("PosOne", array($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ()));
	$arena->save();
	$sender->sendMessage($this->messages->get("pos1_done"));
	$sender->sendMessage($this->messages->get("pos2"));
	}else{$sender->sendMessage($this->messages->get("pos1_needed"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
	}else if($args[0] == "pos2"){
	if(in_array($sender->getName(), $this->creator)){
	if(isset($this->arena["Name"])){
	if(isset($this->arena["pos1"])){
	unset($this->arena["pos1"]);
	/*
	 * =======================
	 * SETTING SECOND POSITION
	 *=======================
	 */
	$this->arena["pos2"] = "pos2";
	$n = $this->arena["Name"];
	$arena = new Config($this->getDataFolder()."Arenas/".$n.".yml", Config::YAML);
	$arena->set("PosTwo", array($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ()));
	$arena->save();
	$sender->sendMessage($this->messages->get("pos2_done"));
	$sender->sendMessage($this->messages->get("armor"));
	}else{$sender->sendMessage($this->messages->get("pos1_needed"));}
	}else{$sender->sendMessage($this->messages->get("name_missing"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
	}else if($args[0] == "armor"){
	if(in_array($sender->getName(), $this->creator)){
	if(isset($this->arena["Name"])){
	if(isset($this->arena["pos2"])){
	unset($this->arena["pos2"]);
	$name = $this->arena["Name"];
	/*
	 * =======================
	 * SETTING ARENA ARMOR
	 *=======================
	 */
	$this->arena["Armor"] = "armor";
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$cap = $sender->getInventory()->getHelmet()->getId();
	$chest = $sender->getInventory()->getChestPlate()->getId();
	$leg = $sender->getInventory()->getLeggings()->getId();
	$boots = $sender->getInventory()->getBoots()->getId();
	$arena->set("Armor", array("helmet" => $cap, "chest" => $chest, "leggings" => $leg, "boots" => $boots));
	$arena->save();
	$sender->sendMessage($this->messages->get("armor_done"));
	$sender->sendMessage($this->messages->get("items"));
	}else{$sender->sendMessage($this->messages->get("pos2_needed"));}
	}else{$sender->sendMessage($this->messages->get("name_missing"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
	}else if($args[0] == "items"){
	if(in_array($sender->getName(), $this->creator)){
	if(isset($this->arena["Name"])){
	if(isset($this->arena["Armor"])){
	unset($this->arena["Armor"]);
	$name = $this->arena["Name"];
	/*
	 * =======================
	 * SETTING ARENA ITEMS
	 *=======================
	 */
	$this->arena["Items"] = "items";
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$items = [];
	foreach($sender->getInventory()->getContents() as $slot => $item){
	$items[$slot] = [$item->getId(), $item->getDamage(), $item->getCount()];
	$arena->set("Items", $items);
    $arena->save();
     }
	$arena->set("Status", "waiting");
	$arena->save();
	unset($this->arena["Name"]);
	unset($this->arena["Items"]);
	unset($this->creator[$sender->getName()]);
	/*
	 * =======================
	 * GAME COMPLETED
	 *=======================
	 */
	$sender->sendMessage($this->messages->get("arena_done"));
	}else{$sender->sendMessage($this->messages->get("armor_need"));}
	}else{$sender->sendMessage($this->messages->get("name_missing"));}
	}else{$sender->sendMessage($this->messages->get("need_creator"));}
    }
    /*
	 * =======================
	 * PRACTICE HELP COMMAND
	 *=======================
	 */
    else 
    if($args[0] == "help"){
	$sender->sendMessage(T::GRAY."-=] ".T::YELLOW."Practice Commands ".T::GRAY."[=-");
	$sender->sendMessage($this->messages->get("practice_make"));
	$sender->sendMessage($this->messages->get("practice_arena"));
	$sender->sendMessage($this->messages->get("practice_editor"));
	$sender->sendMessage($this->messages->get("practice_set_items"));
	$sender->sendMessage($this->messages->get("practice_set_armor"));
	$sender->sendMessage($this->messages->get("practice_arenatype"));
	$sender->sendMessage($this->messages->get("practice_done"));
	$sender->sendMessage(T::YELLOW."Plugin made by: \n".T::AQUA."@BEcraft_MCPE\n".T::WHITE."You".T::RED."Tube".T::YELLOW." BEcraft Gameplay");
	}
	/*
	 * =======================
	 * PRACTICE EDITOR COMMAND
	 *=======================
	 */
    else 
    if($args[0] == "editor"){
	if(!in_array($sender->getName(), $this->editor)){
	$this->editor[$sender->getName()] = $sender->getName();
	$sender->sendMessage($this->messages->get("enable_editor_mode"));
	}
	}
   /*
	 * =======================
	 * SETTING ARENA TYPE 1VS1
	 *=======================
	 */
   else
   if($args[0] == "onevone"){
   if(in_array($sender->getName(), $this->editor)){
   if(isset($args[1])){
   $game = $args[1];
   if($this->arenaExists($game)){
   $arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
   if($config->get("Type") == "kohi"){
   $arena->set("Type", "onevone");
   $arena->save();
   $message = $this->messages->get("set_onevone");
   $message = str_replace("{arena}", $game, $message);
   $sender->sendMessage($message);
   }else{$sender->sendMessage($this->messages->get("already_onevone"));}
   }else{$sender->sendMessage($this->messages->get("game_no_exists"));}
   }
   }else{$sender->sendMessage($this->messages->get("need_editor"));}
   }
   /*
	 * =======================
	 * SETTING ARENA TYPE POTPVP
	 *=======================
	 */
   else 
   if($args[0] == "kohi"){
   if(in_array($sender->getName(), $this->editor)){
   $game = $args[1];
   if($this->arenaExists($game)){
   $arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	if($arena->get("Type") == "onevone"){
	$arena->set("Type", "kohi");
	$arena->save();
	$message = $this->messages->get("set_kohi");
	$message = str_replace("{arena}", $game, $message);
	$sender->sendMessage($message);
	}else{$sender->sendMessage($this->messages->get("already_kohi"));}
	}else{$sender->sendMessage($this->messages->get("game_no_exists"));}
	}else{$sender->sendMessage($this->messages->get("need_editor"));}
	}
	/*
	 * =======================
	 * SETTING NEW ARMOR FOR ANY ARENA
	 *=======================
	 */
    else 
    if($args[0] == "setarmor"){
	if(in_array($sender->getName(), $this->editor)){
	$game = $args[1];
	if($this->arenaExists($game)){
	$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$cap = $sender->getInventory()->getHelmet()->getId();
	$chest = $sender->getInventory()->getChestPlate()->getId();
	$leg = $sender->getInventory()->getLeggings()->getId();
	$boots = $sender->getInventory()->getBoots()->getId();
	$arena->set("Armor", array("helmet" => $cap, "chest" => $chest, "leggings" => $leg, "boots" => $boots));
	$arena->save();
	$message = $this->messages->get("set_new_armor");
	$message = str_replace("{arena}", $game, $message);
	$sender->sendMessage($message);
	}else{$sender->sendMessage($this->messages->get("game_no_exists"));}
	}else{$sender->sendMessage($this->messages->get("need_editor"));}
	}
	/*
	 * =======================
	 * SETTING NEW ITEMS FOR ANY ARENA
	 *=======================
	 */
    else
    if($args[0] == "setitems"){
    if(in_array($sender->getName(), $this->editor)){
	$game = $args[1];
	if($this->arenaExists($game)){
	$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$items = [];
	foreach($sender->getInventory()->getContents() as $slot => $item){
	$items[$slot] = [$item->getId(), $item->getDamage(), $item->getCount()];
	$arena->set("Items", $items);
    $arena->save();
    }
    $message = $this->messages->get("set_new_items");
    $message = str_replace("{arena}", $game, $message);
	$sender->sendMessage($message);
	}else{$sender->sendMessage($this->messages->get("game_no_exists"));}
	}else{$sender->sendMessage($this->messages->get("need_editor"));}
	}
	/*
	 * =======================
	 * LEAVING FROM EDITOR MODE
	 *=======================
	 */
    else 
    if($args[0] == "done"){
	if(in_array($sender->getName(), $this->editor)){
	unset($this->editor[$sender->getName()]);
	$sender->sendMessage($this->messages->get("leave_editor"));
	}else{$sender->sendMessage($this->messages->get("need_editor"));}
	}
	}else{$sender->sendMessage(T::RED."use /practice help");}
	}else{$sender->sendMessage(T::RED."Only for Admins!");}
	return true;
	break;
	
	/*
	 * =======================
	 * ARENA COMMANDS
	 *=======================
	 */
	case "arena":
	if(!$sender instanceof Player) return;
	if(isset($args[0])){
	/*
	 * =======================
	 * JOIN COMMAND
	 *=======================
	 */
	if($args[0] == "join"){
	$game = strtolower($args[1]);
	if(!in_array($sender->getName(), $this->playing)){
    if($this->arenaExists($game)){
	$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$name = $arena->get("Name");
    $level = $arena->get("Level");
	if($this->gamesCount($game) == 0){
	$this->newTask($game);
	$pone = $arena->get("PosOne");
	$x = $pone[0];
	$z = $pone[2];
	$y = $pone[1];
	$ar = $this->getServer()->getLevelByName($level);
	$sender->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($sender, $game);
	$this->setKit($sender, $game);
	}else
	if($this->gamesCount($game) == 1){
	$ptwo = $arena->get("PosTwo");
	$x = $ptwo[0];
	$z = $ptwo[2];
	$y = $ptwo[1];
	$ar = $this->getServer()->getLevelByName($level);
	$sender->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($sender, $game);
	$this->setKit($sender, $game);
	}else if($this->getPlayers($game) == 2){
	$sender->sendMessage($this->messages->get("already_started"));
	}
    }else{$sender->sendMessage($this->messages->get("game_no_exists"));}
	}else{
	$sender->sendMessage($this->messages->get("already_playing"));
	}
	}
	/*
	 * =======================
	 * ARENA LIST COMMAND
	 *=======================
	 */
    else
	if($args[0] == "list"){
	$sender->sendMessage(T::GREEN."Arenas:");
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $file){
	if($file !== ".." and $file !== "."){
	$name = str_replace(".yml", "", $file);
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$status = $arena->get("Status");
	if($status == "not set"){
	$estado = T::GREEN."arena is being created...";
	}else if($status == "waiting"){
	$estado = T::GREEN.$this->gamesCount($name)."/2";
	}else if($status == "running"){
	$estado = T::RED."running";
	}
	$sender->sendMessage(T::YELLOW.$name.T::AQUA.T::BOLD." > ".T::RESET.$estado."\n");
	}
	}
	}
	/*
	 * =======================
	 * ARENA QUIT COMMAND
	 *=======================
	 */
    else 
	if($args[0] == "quit"){
    if(in_array($sender->getName(), $this->playing)){
	unset($this->playing[$sender->getName()]);
	unset($this->move[$sender->getName()]);
	$this->deletePlayer($sender);
	$sender->removeAllEffects();
	$sender->setHealth(20);
	$sender->getInventory()->clearAll();
	$sender->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	$sender->sendMessage($this->messages->get,("you_left_from_game"));
	}else{
	$sender->sendMessage($this->messages->get("you_are_no_playing"));
	}
	}
	/*
	 * =======================
	 * ARENA DUEL COMMAND
	 *=======================
	 */
    else 
	if($args[0] == "duel"){
	if($sender->getLevel() !== Server::getInstance()->getDefaultLevel()) return;
	if(in_array($sender->getName(), $this->playing)) return;
	$vs = $args[1];
	$player = $sender->getServer()->getPlayer($vs);
	if($player instanceof Player){
	if($player->getName() !== $sender->getName()){
	if(!isset($this->request[$player->getName()])){
	if(!isset($this->request[$sender->getName()])){
	if(!isset($this->sent[$sender->getName()])){
	if(!isset($this->sent[$player->getName()])){
	$this->request[$player->getName()] = $sender->getName();
	$this->sent[$sender->getName()] = $player->getName();
	$sent = $this->messages->get("sent_request");
	$sent = str_replace("{player}", $player->getName(), $sent);
	$sender->sendMessage($sent);
	$request = $this->messages->get("new_request");
	$request = str_replace("{sender}", $sender->getName(), $request);
	$player->sendMessage($request);
	}else{$sender->sendMessage($this->messages->get("requested"));}
	}else{$sender->sendMessage($this->messages->get("already_sent_request"));}
	}else{$sender->sendMessage($this->messages->get("cannot_duel"));}
	}else{$sender->sendMessage($this->messages->get("duel_process"));}
	}else{$sender->sendMessage($this->messages->get("no_duel_youselft"));}
	}else{$sender->sendMessage($this->messages->get("player_offline"));}
	}
	/*
	 * =======================
	 * ARENA DUEL ACCEPT COMMAND
	 *=======================
	 */
    else 
	if($args[0] == "accept"){
	if($sender->getLevel() !== Server::getInstance()->getDefaultLevel()) return;
	if(in_array($sender->getName(), $this->playing)) return;
	if(isset($this->request[$sender->getName()])){
	if($args[1] == $this->request[$sender->getName()]){
	$search = $this->request[$sender->getName()];
	$player = $sender->getServer()->getPlayer($search);
	if($player instanceof Player){
    unset($this->request[$sender->getName()]);
	unset($this->sent[$player->getName()]);
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $arenas){
	if($arenas !== ".." and $arenas !== "."){
	$name = str_replace(".yml", "", $arenas);
	if($this->arenaExists($name)){
	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
	$status = $arena->get("Status");
	$level = $arena->get("Level");
	if($this->gamesCount($name) == 0 and $status == "waiting" and $player->getLevel() !== $level){
	/*
	 * =======================
	 * ADD FIRST PLAYER TO ARENA
	 *=======================
	 */
	$this->newTask($name);
	$pone = $arena->get("PosOne");
	$x = $pone[0];
	$z = $pone[2];
	$y = $pone[1];
	$ar = $this->getServer()->getLevelByName($level);
	$sender->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($sender, $name);
	$this->setKit($sender, $name);
	/*
	 * =======================
	 * ADD SECOND PLAYER TO ARENA
	 *=======================
	 */
    $this->playing[$player->getName()] = $player->getName();
	$this->move[$player->getName()] = $player->getName();
	$ptwo = $arena->get("PosTwo");
	$x = $ptwo[0];
	$z = $ptwo[2];
	$y = $ptwo[1];
	$ar = $this->getServer()->getLevelByName($level);
	$player->teleport(new Position($x, $y, $z, $ar));
	$this->addPlayer($player, $name);
	$this->setKit($player, $name);
	}else{
	$sender->sendMessage($this->messages->get("no_free_arenas"));
	$player->sendMessage($this->messages->get("no_free_arenas"));
	}
	}else{
	$sender->sendMessage($this->messages->get("no_free_arenas"));
	$player->sendMessage($this->messages->get("no_free_arenas"));
	}
	}
	}
	}else{$sender->sendMessage($this->messages->get("player_offline"));}
	}else{$sender->sendMessage($this->messages->get("not_request"));}
	}else{$sender->sendMessage($this->messages->get("none_request"));}
	}
	/*
	 * =======================
	 * ARENA DECLINE DUEL COMMAND
	 *=======================
	 */
    else 
    if($args[0] == "decline"){
	if(isset($this->request[$sender->getName()])){
	$p = $this->request[$sender->getName()];
	if($args[1] == $p){
	$player = $sender->getServer()->getPlayer($p);
	unset($this->request[$sender->getName()]);
	$decline = $this->messages->get("decline");
	$decline = str_replace("{player}", $player->getName(), $decline);
	$sender->sendMessage($decline);
	unset($this->sent[$player->getName()]);
	$declined = $this->messages->get("declined");
	$declined = str_replace("{sender}", $sender->getName(), $declined);
	$player->sendMessage($declined);
	}else{$sender->sendMessage($this->messages->get("no_request_sent"));}
	}else{$sender->sendMessage($this->messages->get("none_request"));}
	}
	/*
	 * =======================
	 * ARENA REQUEST COMMAND
	 *=======================
	 */
    else 
    if($args[0] == "request"){
	if(isset($this->request[$sender->getName()])){
	$sender->sendMessage(T::YELLOW."Request: ".T::GREEN.$this->request[$sender->getName()]);
	}else if(isset($this->sent[$sender->getName()])){
	$sender->sendMessage(T::YELLOW."Sent: ".$this->sent[$sender->getName()]);
	}else if(isset($this->request[$sender->getName()]) and isset($this->sent[$sender->getName()])){
	$sender->sendMessage(T::YELLOW."Request: ".T::GREEN.$this->request[$sender->getName()]);
	$sender->sendMessage(T::YELLOW."Sent: ".T::GREEN.$this->sent[$sender->getName()]);
	}
    }
    /*
	 * =======================
	 * REMOVE ANY REQUEST COMMAND
	 *=======================
	 */
    else if($args[0] == "rsent"){
	if(isset($this->sent[$sender->getName()])){
    $sent = $this->sent[$sender->getName()];
	$player = $sender->getServer()->getPlayer($sent);
	if($player instanceof Player){
	unset($this->request[$player->getName()]);
	unset($this->sent[$sender->getName()]);
	$sender->sendMessage($this->messages->get("cancelled_request"));
	$cancel = $this->messages->get("cancel");
	$cancel = str_replace("{sender}", $sender->getName(), $cancel);
	$player->sendMessage($cancel);
	}
	}else{$sender->sendMessage($this->messages->get("no_duel_request_sent"));}
	}
    }else{
    $sender->sendMessage(T::YELLOW."Commands:".T::GREEN." /arena [join <arena>] [quit] [list] [duel <player>] [accept <player>] [decline <player>] [rsent]");}
	return true;
	break;
	}
	}
	}