<?php

namespace BEcraft\Minigame\task;

use pocketmine\scheduler\PluginTask;
use BEcraft\Minigame\Main;
use pocketmine\Server;
use pocketmine\utils\TextFormat as T;
use pocketmine\utils\Config;

class GameTask extends PluginTask{
	
	public $time = 500;
	public $status = "waiting";
	public $game;
	public $plugin;
	
	public function __construct(Main $main, $game){
	parent::__construct($main);
	$this->plugin = $main;
	$this->game = $game;
	$this->time = 500;
	}
	
	public function getTime($int) {
                $m = floor($int / 60);
                $s = floor($int % 60);
                return (($m < 10 ? "0" : "") . $m . ":" . ($s < 10 ? "0" : "") . $s);
            }
            
	public function onRun($tick){
	$game = $this->game;
	if($this->plugin->arenaExists($game)){
	$config = new Config($this->plugin->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$name = $config->get("Name");
	$level = $config->get("Level");
	if($this->getCount($game) == 1 and $this->status == "waiting"){
		$config->set("Status", "waiting");
		$config->save();
		foreach($this->getPlaying($game) as $player){
			$player->sendPopup(T::YELLOW."Waiting for players: ".T::GOLD.$this->getCount($game)." | 2");
					}
	}else
if($this->getCount($game) == 2){
			$this->time--;
			$this->status = "running";
			$config->set("Status", "running");
		    $config->save();
		    foreach($this->getPlaying($game) as $player){
			$player->sendPopup(T::YELLOW."Time: ".T::GOLD.$this->getTime($this->time));
			}
		}else if($this->getCount($game) == 1 and $this->status == "running" and $this->time > 0){
		$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
		foreach($this->getPlaying($game) as $player){
		$player->getInventory()->clearAll();
		$player->setHealth(20);
		Server::getInstance()->broadcastMessage(T::GOLD.$player->getName()." won a duel in arena: ".T::YELLOW.$game);
		unset($this->plugin->playing[$player->getName()]);
		$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		$this->status = "waiting";
		$config->set("Status", "waiting");
		$config->save();
		$this->time = 500;
						}
						
					}else if($this->getCount($game) == 2 and $this->time == 0){
						$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
		foreach($this->getPlaying($game) as $player){
		$player->getInventory()->clearAll();
		$player->setHealth(20);
		unset($this->plugin->playing[$player->getName()]);
		$player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
		Server::getInstance()->broadcastMessage(T::RED."Nobody won in arena: ".T::YELLOW.$game);
		$player->sendMessage(T::GRAY."Time is over, good luck at next!");
		$this->status = "waiting";
		$config->set("Status", "waiting");
		$config->save();
		$this->time = 500;
		}
		}
	}
	}//public function
	
	public function getPlaying($game){
		return $this->plugin->getPlaying($game);
		}
	
	public function getCount($game){
		return $this->plugin->getPlayers($game);
	}
}