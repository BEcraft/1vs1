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
	if(!empty($arena->get("Level"))){
	$level = $arena->get("Level");
	$this->getServer()->loadLevel($level);
	$level->setTime(0);
	$level->stopTime();
	$this->getLogger()->notice(T::GOLD."\nLoading arenas: \n".T::GREEN.$level);
	}
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
	Server::getInstance()->broadcastMessage(T::GOLD.$victim->getName().T::GRAY." has been killed by ".T::GREEN.$killer->getName());
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
	$p->sendMessage(T::GREEN."Game sign for arena ".T::GOLD.$arena_name.T::GREEN." created!");
	$p->getLevel()->addSound(new AnvilUseSound(new Vector3($p->x, $p->y, $p->z)));
	}else{
	$p->sendMessage(T::RED."There is not any arena with that name!");
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
	$p->sendMessage(T::RED."Game already started!");
	}
	}else{$p->sendMessage(T::RED."You are already playing!");}
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
	if(in_array($p->getName(), $this->move)){
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
	public function setKohi(Player $player){
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
	foreach($in as $slot => $item){
	$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2]));
	}
	}else{
	$this->setKohi($player);
	}
	Server::getInstance()->broadcastMessage(T::GOLD.$player->getName().T::YELLOW." joined to fight at ".T::GREEN.$game);
	$player->sendMessage(T::GREEN."You joined to {$game} arena. ".T::RED.$this->gamesCount($game)."/2");
	foreach($this->games[$game] as $jugador){
	$jugador->sendMessage(T::YELLOW.$player->getName().T::GRAY." joined to game!");
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
	$sender->sendMessage(T::GREEN."Start setting arena name with /practice name <name>");
	}else{$sender->sendMessage(T::RED."there is a player creating an arena!");}
	}else{$sender->sendMessage(T::RED."You are in creator mode!");}
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
	$sender->sendMessage(T::GREEN."Arena {$name} created!");
	$sender->sendMessage(T::GOLD."Continue with ".T::YELLOW."/practice pos1");
	}else{$sender->sendMessage(T::RED."There is a game with that name... please enter other name!");}
	}else{$sender->sendMessage(T::RED."Please not numbers!");}
	}else{$sender->sendMessage(T::RED."You need to be in creator mode!");}
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
	$sender->sendMessage(T::YELLOW."First position set correctly!");
	$sender->sendMessage(T::GOLD."Continue with ".T::YELLOW."/practice pos2");
	}else{$sender->sendMessage(T::RED."You need to set arena name first!");}
	}else{$sender->sendMessage(T::RED."You are in creator mode!");}
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
	$sender->sendMessage(T::YELLOW."Positions added correctly!");
	$sender->sendMessage(T::GOLD."Continue with ".T::YELLOW."/practice armor");
	}else{$sender->sendMessage(T::RED."You need to set first position!");}
	}else{$sender->sendMessage(T::RED."You need to set a name first!");}
	}else{$sender->sendMessage(T::RED."You are in creator mode!");}
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
	$sender->sendMessage(T::YELLOW."Armor added correctly!");
	$sender->sendMessage(T::GOLD."Continue with ".T::YELLOW."/practice items");
	}else{$sender->sendMessage(T::RED."You need to set second position first!");}
	}else{$sender->sendMessage(T::RED."You need to set a name first!");}
	}else{$sender->sendMessage(T::RED."You are in creator mode!");}
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
	$sender->sendMessage(T::GREEN."Game created completed!");
	}else{$sender->sendMessage(RED."You need to set armor first!");}
	}else{$sender->sendMessage(T::RED."You need to set a name first!");}
	}else{$sender->sendMessage(T::RED."You need to be in creator mode!");}
    }
    /*
	 * =======================
	 * PRACTICE HELP COMMAND
	 *=======================
	 */
    else 
    if($args[0] == "help"){
	$sender->sendMessage(T::GRAY."-=] ".T::YELLOW."Practice Commands ".T::GRAY."[=-");
	$sender->sendMessage(T::GREEN."/practice make ".T::GRAY."[".T::GOLD."Create a new arena!".T::GRAY."]");
	$sender->sendMessage(T::GREEN."/arena ".T::GRAY."[".T::GOLD."See arena commands");
	$sender->sendMessage(T::GREEN."/practice editor ".T::GRAY."[".T::GOLD."Join to editor mode!".T::GRAY."]");
	$sender->sendMessage(T::GREEN."/practice setitems <arena> ".T::GRAY."[".T::GOLD."Set new items for any arena (you have to be in editor mode!)");
	$sender->sendMessage(T::GREEN."/practice setarmor <arena> ".T::GOLD."Set new armor to any arena (hou have to be in creator mode!)");
	$sender->sendMessage(T::GREEN."/practice <onevone | kohi> <arena> ".T::GRAY."[".T::GOLD."Set any arena type (you need to be in creator mode!)");
	$sender->sendMessage(T::GREEN."/practice done ".T::GRAY."[".T::GOLD."Leave from editor mode!".T::GRAY."]");
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
	$sender->sendMessage(T::GREEN."You are in editor mode now!");
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
   $sender->sendMessage(T::GREEN."Changed type of game to 1vs1 correctly for arena ".T::GOLD.$game);
   }else{$sender->sendMessage(T::YELLOW."This arena already is 1vs1");}
   }else{$sender->sendMessage(T::RED."Sorry this game not exists!");}
   }
   }else{$sender->sendMessage(T::RED."You need to be in editor mode to use this command");}
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
	$sender->sendMessage(T::GREEN."Changed type of game to kohi correctly for arena ".T::GOLD.$game);
	}else{$sender->sendMessage(T::YELLOW."This arena already is kohi");}
	}else{$sender->sendMessage(T::RED."Sorry this game not exists!");}
	}
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
	$sender->sendMessage(T::GREEN."Changed armor correctly for arena ".T::GOLD.$game);
	}else{$sender->sendMessage(T::RED."Sorry this game not exists!");}
	}
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
	$sender->sendMessage(T::GREEN."Changed items correctly for arena ".T::GOLD.$game);
	}else{$sender->sendMessage(T::RED."Sorry this game not exists!");}
	}
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
	$sender->sendMessage(T::RED."You left from creator mode!");
	}
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
	$sender->sendMessage(T::RED."Game started!");
	}
    }else{$sender->sendMessage(T::GOLD.$name.T::RED." Doesnt exist...");}
	}else{
	$sender->sendMessage(T::RED."You are already playing!");
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
	$sender->sendMessage(T::YELLOW."You left from arena!");
	}else{
	$sender->sendMessage(T::RED."You are not playing!");
	}
	}
	/*
	 * =======================
	 * ARENA DUEL COMMAND
	 *=======================
	 */
    else 
	if($args[0] == "duel"){
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
	$sender->sendMessage(T::GREEN."You sent a duel request to ".T::RED.$player->getName());
	$player->sendMessage(T::GREEN.$sender->getName().T::GOLD." Sent you a duel request, type /arena accept ".T::GREEN.$sender->getName().T::GOLD." to accept it!");
	}else{$sender->sendMessage(T::RED."This player sent a request to other player!");}
	}else{$sender->sendMessage(T::RED."You already sent a request to other player, if you want cancel it use /arena!");}
	}else{$sender->sendMessage(T::RED."You have to decline or accept your current request for duel other player!");}
	}else{$sender->sendMessage(T::RED."This player has a request in process!");}
	}else{$sender->sendMessage(T::RED."You cant send request to yourselft xD");}
	}else{$sender->sendMessage(T::RED."Sorry this player is not online!");}
	}
	/*
	 * =======================
	 * ARENA DUEL ACCEPT COMMAND
	 *=======================
	 */
    else 
	if($args[0] == "accept"){
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
	$sender->sendMessage(T::RED."There is not any free arena to fight!");
	$player->sendMessage(T::RED."There is not any free arena to fight!");
	}
	}else{
	$sender->sendMessage(T::RED."There is not any free arena to fight!");
	$player->sendMessage(T::RED."There is not any free arena to fight!");
	}
	}
	}
	}else{$sender->sendMessage(T::RED."Sorry this player is not online!");}
	}else{$sender->sendMessage(T::RED."Sorry this player is not in your request list!");}
	}else{$sender->sendMessage(T::RED."You dont have any duel request!");}
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
	$sender->sendMessage(T::RED."You declined ".T::GREEN.$player->getName().T::RED." request!");
	unset($this->sent[$player->getName()]);
	$player->sendMessage(T::GREEN.$sender->getName().T::RED." declined your request!");
	}else{$sender->sendMessage(T::RED."Sorry this player didnt send you any request!");}
	}else{$sender->sendMessage(T::RED."You dont have any request!");}
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
	$sender->sendMessage(T::GREEN."You cancelled your duel request!");
	$player->sendMessage(T::RED.$sender->getName().T::GOLD." cancelled the duel request!");
	}
	}else{$sender->sendMessage(T::RED."You didnt send any duel request");}
	}
    }else{
    $sender->sendMessage(T::YELLOW."Commands:".T::GREEN." /arena [join <arena>] [quit] [list] [duel <player>] [accept <player>] [decline <player>] [rsent]");}
	return true;
	break;
	}
	}
	}