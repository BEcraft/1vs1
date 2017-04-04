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
use BEcraft\Minigame\task\GameTask;
use BEcraft\Minigame\task\SignTask;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as T;
use pocketmine\level\Position;
use pocketmine\level\Level;

class Main extends PluginBase implements Listener{
	
         /* Arena Maker */
	public $creator = array();
	
	           /* Arena */
	public $arena = array();
	
	           /* Playing */
	public $playing = array();
	
	           /* Game */
	public $game = array();
	
	      /* Dont Move */
	public $move = array();
	
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
	$this->getLogger()->info(T::GREEN."Enabled!");
	}
	
	public function createConfig(){
	@mkdir($this->getDataFolder());
	@mkdir($this->getDataFolder()."Arenas/");
	}
	
	public function updateSign(){
	$this->getServer()->getScheduler()->scheduleRepeatingTask(new SignTask($this), 30)->getTaskId();
		}
	
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
			}
		}
		}
		}
		
	public function newTask($game){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameTask($this, $game), 30)->getTaskId();
		}
	
	public function loadArenas(){
		if(!empty($this->getDataFolder()."Arenas/")){
	$scan = scandir($this->getDataFolder()."Arenas/");
	foreach($scan as $files){
		if($files !== ".." and $files !== "."){
			$name = str_replace(".yml", "", $files);
			$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
			$level = $arena->get("Level");
			$this->getServer()->loadLevel($level);
			$this->getLogger()->notice(T::GOLD."\nLoading arenas: \n".T::GREEN.$level);
			}
		}
		}
	}
	
	public function getCount(){
		return count($this->playing);
		}
		
	public function getPlaying($game){
		if($this->arenaExists($game)){
	    $arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	return $this->getServer()->getLevelByName($arena->get("Level"))->getPlayers();
			}
		}
		
	public function getPlayers($game){
		if($this->arenaExists($game)){
	    $arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	return count($this->getServer()->getLevelByName($arena->get("Level"))->getPlayers());
			}
		}
		
	public function onQuit(PlayerQuitEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->playing)){
		unset($this->playing[$p->getName()]);
		}
	}
	
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
				Server::getInstance()->broadcastMessage(T::GOLD.$victim->getName().T::GRAY." lost a duel against ".T::GREEN.$killer->getName());
				$victim->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
				unset($this->playing[$victim->getName()]);
				}
			}
		}
		}
	}
	
	
	public function arenaExists($name){
		if(file_exists($this->getDataFolder()."Arenas/".$name.".yml")){
			return true;
			}else{
				return false;
				}
		}
	
	public function onJoinEvent(PlayerJoinEvent $e){
	    $pl = $e->getPlayer();
	    $pl->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
		}
		
	public function onMove(PlayerMoveEvent $e, $game){
		$p = $e->getPlayer();
		$scan = scandir($this->getDataFolder()."Arenas/");
		foreach($scan as $files){
			if($files !== ".." and $files !== "."){
				$game = str_replace(".yml", "", $files);
				$arena = new Config($this->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
				$level = $arena->get("Level");
				$status = $arena->get("Status");
				if($p->getLevel()->getFolderName() == $level){
					if($status !== "running"){
							$to = clone $e->getFrom();
			$to->yaw = $e->getTo()->yaw;
			$to->pitch = $e->getTo()->pitch;
			$e->setTo($to);
					}
				}
			}
		}
		}
	
	public function signChange(SignChangeEvent $e){
	$p = $e->getPlayer();
	if($p->isOp()){
		if($e->getLine(0) == "practice"){
			$name = $e->getLine(1);
			if($this->arenaExists($name)){
				$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
				$arena_name = $arena->get("Name");
				$arena_level = $arena->get("Level");
				$e->setLine(0, "practice");
				$e->setLine(1, $arena_name);
				$e->setLine(2, $arena_level);
				$e->setLine(3, "");
				$p->sendMessage(T::GREEN."Game sign for arena ".T::GOLD.$arena_name.T::GREEN." created!");
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
	
	public function onBreak(BlockBreakEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->playing)){
		$e->setCancelled(true);
		}
	}
	
	public function onPlace(BlockPlaceEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->playing)){
		$e->setCancelled(true);
		}
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
	switch($command->getName()){
		case "practice":
		
			if($sender->isOp()){
				if(isset($args[0])){
					if($args[0] == "make"){
					if(!in_array($sender->getName(), $this->creator)){
						if(count($this->creator) == 0){
							$this->creator[$sender->getName()] = $sender->getName();
							$sender->sendMessage(T::GREEN."Start setting arena name with /practice name <name>");
						}else{$sender->sendMessage(T::RED."there is a player creating an arena!");}
					}else{$sender->sendMessage(T::RED."You are in creator mode!");}
					}else if($args[0] == "name"){
						if(in_array($sender->getName(), $this->creator)){
							if(!is_numeric($args[1])){
								if(!file_exists($this->getDataFolder()."Arenas/".$args[1].".yml")){
							$name = $args[1];
							$this->arena["Name"] = $name;
				$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML, [
							"Name" => $name,
							"Level" =>  $sender->getLevel()->getFolderName(),
							"PosOne" => "not set",
							"PosTwo" => "no set",
							"Armor" => "no set",
							"Items" => "no set",
							"Status" => "not set",
							]);
							$arena->save();
							$sender->sendMessage(T::GREEN."Arena {$name} created!");
							$sender->sendMessage(T::GOLD."Continue with ".T::YELLOW."/practice pos1");
							}else{$sender->sendMessage(T::RED."There is a game with that name... please enter other name!");}
							}else{$sender->sendMessage(T::RED."Please not numbers!");}
						}else{$sender->sendMessage(T::RED."You need to be in creator mode!");}
					}else if($args[0] == "pos1"){
					if(in_array($sender->getName(), $this->creator)){
						if(isset($this->arena["Name"])){
							$this->arena["pos1"] = "pos1";
							$name = $this->arena["Name"];
							$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
							$arena->set("PosOne", array("x" => $sender->getFloorX(), "z" => $sender->getFloorZ()));
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
								$this->arena["pos2"] = "pos2";
								$n = $this->arena["Name"];
								$arena = new Config($this->getDataFolder()."Arenas/".$n.".yml", Config::YAML);
								$arena->set("PosTwo", array("x" => $sender->getFloorX(), "z" => $sender->getFloorZ()));
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
									$this->arena["Items"] = "items";
							    	$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
							
							    $zero = $sender->getInventory()->getHotbarSlotIndex(0);
								$item_zero = $sender->getInventory()->getItem($zero)->getId();
								$item_zero_count = $sender->getInventory()->getItem($zero)->getCount();
								$item_zero_damage = $sender->getInventory()->getItem($zero)->getDamage();
								
								$one = $sender->getInventory()->getHotbarSlotIndex(1);
								$item_one = $sender->getInventory()->getItem($one)->getId();
								$item_one_count = $sender->getInventory()->getItem($one)->getCount();
								$item_one_damage = $sender->getInventory()->getItem($one)->getDamage();
								
								$two = $sender->getInventory()->getHotbarSlotIndex(2);
								$item_two = $sender->getInventory()->getItem($two)->getId();
								$item_two_count = $sender->getInventory()->getItem($two)->getCount();
								$item_two_damage = $sender->getInventory()->getItem($two)->getDamage();
								
								$three = $sender->getInventory()->getHotbarSlotIndex(3);
								$item_three = $sender->getInventory()->getItem($three)->getId();
								$item_three_count = $sender->getInventory()->getItem($three)->getCount();
								$item_three_damage = $sender->getInventory()->getItem($three)->getDamage();
								
								$four = $sender->getInventory()->getHotbarSlotIndex(4);
								$item_four = $sender->getInventory()->getItem($four)->getId();
								$item_four_count = $sender->getInventory()->getItem($four)->getCount();
								$item_four_damage = $sender->getInventory()->getItem($four)->getDamage();
								
								$five = $sender->getInventory()->getHotbarSlotIndex(5);
								$item_five = $sender->getInventory()->getItem($five)->getId();
								$item_five_count = $sender->getInventory()->getItem($five)->getCount();
								$item_five_damage = $sender->getInventory()->getItem($five)->getDamage();
								
							    	$arena->set("Items", array("zero" => ["item" => $item_zero, "damage" => $item_zero_damage, "count" => $item_zero_count], "one" => ["item" => $item_one, "damage" => $item_one_damage, "count" => $item_one_count], "two" => ["item" => $item_two, "damage" => $item_two_damage, "count" => $item_two_count], "three" => ["item" => $item_three, "damage" => $item_three_damage, "count" => $item_three_count], "four" => ["item" => $item_four, "damage" => $item_four_damage, "count" => $item_four_count], "five" => ["item" => $item_five, "damage" => $item_five_damage, "count" => $item_five_count]));
							    	$arena->save();
							        $arena->set("Status", "waiting");
							        $arena->save();
							    	unset($this->arena["Name"]);
							    	unset($this->arena["Items"]);
							    	unset($this->creator[$sender->getName()]);
							    	$sender->sendMessage(T::GREEN."Game created completed!");
								}else{$sender->sendMessage(RED."You need to set armor first!");}
							}else{$sender->sendMessage(T::RED."You need to set a name first!");}
						}else{$sender->sendMessage(T::RED."You need to be in creator mode!");}
					}else if($args[0] == "help"){
						$sender->sendMessage(T::GRAY."-=] ".T::YELLOW."Practice Commands ".T::GRAY."[=-\n".T::GREEN."/practice make ".T::GOLD."Create a new arena!\n".T::GREEN."/arena ".T::GOLD."See arena commands\n".T::YELLOW."Plugin made by: \n".T::AQUA."@BEcraft_MCPE\n".T::WHITE."You".T::RED."Tube".T::YELLOW." BEcraft Gameplay");
						}
				}else{$sender->sendMessage(T::RED."use /practice help");}
			}else{$sender->sendMessage(T::RED."Only for Admins!");}
		return true;
		break;
		
		case "arena":
		if(isset($args[0])){
			
			if($args[0] == "join"){
				$name = $args[1];
				if(!in_array($sender->getName(), $this->playing)){
				if($this->arenaExists($name)){
					$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
					$name = $arena->get("Name");
					$level = $arena->get("Level");
					$game = $args[1];
					if($this->getPlayers($game) == 0){
						$this->playing[$sender->getName()] = $sender->getName();
						$sender->setHealth(20);
						$this->newTask($game);
						$pone = $arena->get("PosOne");
						$x = $pone["x"];
						$z = $pone["z"];
						$ar = $this->getServer()->getLevelByName($level);
						$y = $ar->getHighestBlockAt($x, $z);
						$sender->teleport(new Position($x, $y+1, $z, $ar));
						$sender->getInventory()->clearAll();
						$armor = $arena->get("Armor");
						$h = $armor["helmet"];
						$c = $armor["chest"];
						$l = $armor["leggings"];
						$b = $armor["boots"];
						$sender->getInventory()->setHelmet(Item::get($h));
						$sender->getInventory()->setChestPlate(Item::get($c));
						$sender->getInventory()->setLeggings(Item::get($l));
						$sender->getInventory()->setBoots(Item::get($b));
						$item = $arena->get("Items");
						$zero = $item["zero"];
						//slot 0
						$sender->getInventory()->addItem(Item::get($zero["item"], $zero["damage"], $zero["count"]));
						//slot 1
						$one = $item["one"];
						$sender->getInventory()->addItem(Item::get($one["item"], $one["damage"], $one["count"]));
						//slot 2
						$two = $item["two"];
						$sender->getInventory()->addItem(Item::get($two["item"], $two["damage"], $two["count"]));
						//slot 3
						$three = $item["three"];
						$sender->getInventory()->addItem(Item::get($three["item"], $three["damage"], $three["count"]));
						//slot 4
						$four = $item["four"];
						$sender->getInventory()->addItem(Item::get($four["item"], $four["damage"], $four["count"]));
						//slot 5
						$five = $item["five"];
						$sender->getInventory()->addItem(Item::get($five["item"], $five["damage"], $five["count"]));
						$sender->sendMessage(T::GREEN."You joined to {$name} arena. ".T::RED.$this->getPlayers($game)."/2");
						foreach($this->getPlaying($game) as $jugador){
							$jugador->sendMessage(T::YELLOW.$sender->getName().T::GRAY." joined to game!");
							}
						}else
						if($this->getPlayers($game) == 1){
						$this->playing[$sender->getName()] = $sender->getName();
						$sender->setHealth(20);
						$ptwo = $arena->get("PosTwo");
						$x = $ptwo["x"];
						$z = $ptwo["z"];
						$ar = $this->getServer()->getLevelByName($level);
						$y = $ar->getHighestBlockAt($x, $z);
						$sender->teleport(new Position($x, $y+1, $z, $ar));
						$sender->getInventory()->clearAll();
						$items = $arena->get("Armor");
						$h = $items["helmet"];
						$c = $items["chest"];
						$l = $items["leggings"];
						$b = $items["boots"];
						$sender->getInventory()->setHelmet(Item::get($h));
						$sender->getInventory()->setChestPlate(Item::get($c));
						$sender->getInventory()->setLeggings(Item::get($l));
						$sender->getInventory()->setBoots(Item::get($b));
						$item = $arena->get("Items");
						$zero = $item["zero"];
						//slot 0
						$sender->getInventory()->addItem(Item::get($zero["item"], $zero["damage"], $zero["count"]));
						//slot 1
						$one = $item["one"];
						$sender->getInventory()->addItem(Item::get($one["item"], $one["damage"], $one["count"]));
						//slot 2
						$two = $item["two"];
						$sender->getInventory()->addItem(Item::get($two["item"], $two["damage"], $two["count"]));
						//slot 3
						$three = $item["three"];
						$sender->getInventory()->addItem(Item::get($three["item"], $three["damage"], $three["count"]));
						//slot 4
						$four = $item["four"];
						$sender->getInventory()->addItem(Item::get($four["item"], $four["damage"], $four["count"]));
						//slot 5
						$five = $item["five"];
						$sender->getInventory()->addItem(Item::get($five["item"], $five["damage"], $five["count"]));
						$sender->sendMessage(T::GREEN."You joined to {$name} arena. ".T::RED.$this->getPlayers($game)."/2");
						foreach($this->getPlaying($game) as $jugador){
							$jugador->sendMessage(T::YELLOW.$sender->getName().T::GRAY." joined to game!");
							}
							}else if($this->getPlayers($game) >= 2){
								$sender->sendMessage(T::RED."Game started!");
								}
					}else{$sender->sendMessage(T::GOLD.$name.T::RED." Doesnt exist...");}
					}else{
						$sender->sendMessage(T::RED."You are already playing!");
						}
				}else
				if($args[0] == "list"){
					$sender->sendMessage(T::GREEN."Arenas:");
					$scan = scandir($this->getDataFolder()."Arenas/");
					foreach($scan as $file){
						if($file !== ".." and $file !== "."){
							$name = str_replace(".yml", "", $file);
							$arena = new Config($this->getDataFolder()."Arenas/".$name.".yml", Config::YAML);
							$status = $arena->get("Status");
							if($status == "not set"){
								$estado = "arena is being created...";
								}else if($status == "waiting"){
									$estado = "waiting for players!";
									}else if($status == "starting"){
										$estado = "arena is starting a game!";
										}else if($status == "running"){
											$estado = "running, so wait.";
											}
											$sender->sendMessage(T::YELLOW.$name.T::AQUA." | ".T::GOLD.$estado."\n");
				}
						}
					}else if($args[0] == "quit"){
						if(in_array($sender->getName(), $this->playing)){
							unset($this->playing[$sender->getName()]);
							$sender->getInventory()->clearAll();
							$sender->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
							$sender->sendMessage(T::YELLOW."You left from arena!");
							}
						}
			}else{$sender->sendMessage(T::RED."use /arena [join <arena>] [quit] [list]");}
			return true;
			break;
	}
	}
	
	}