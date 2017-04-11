<?php

namespace BEcraft\Minigame\task;

use pocketmine\scheduler\PluginTask;
use BEcraft\Minigame\Main;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat as T;
use pocketmine\utils\Config;

class GameTask extends PluginTask{
	
	public $time = 15;
	public $status = "waiting";
	public $game;
	public $plugin;
	
	public function __construct(Main $main, $game){
	parent::__construct($main);
	$this->plugin = $main;
	$this->game = $game;
	$this->time = 15;
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
			            $blind = Effect::getEffect(15);
						$blind->setDuration(9999);
						$blind->setAmplifier(10);
						$blind->setVisible(false);
						$player->addEffect($blind);
					}
	}else
if($this->getCount($game) == 2){
			$this->time--;
			$this->status = "running";
			$config->set("Status", "running");
		    $config->save();
		    foreach($this->getPlaying($game) as $player){
			$player->sendPopup(T::YELLOW."Time: ".T::GOLD.$this->getTime($this->time));
			if($this->time == 14){
				$player->sendMessage(T::GREEN."Game started, good luck!");
				$player->removeAllEffects();
				unset($this->plugin->move[$player->getName()]);
				}
			}
		}else if($this->getPlaying($game) >= 2 and $this->status == "running" and $this->time > 0){
		$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
		foreach($this->getPlaying($game) as $player){
		$player->getInventory()->clearAll();
		$player->setHealth(20);
		Server::getInstance()->broadcastMessage(T::GOLD.$player->getName()." won a duel in arena: ".T::YELLOW.$game);
		unset($this->plugin->playing[$player->getName()]);
		$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		$this->plugin->WinTask($player);
		$this->status = "waiting";
		$config->set("Status", "waiting");
		$config->save();
		$this->time = 15;
						}
					}else if($this->getCount($game) == 0){
			$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
			}
	 if($this->getCount($game) >= 2 and $this->status == "running" and $this->time < 1){
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
		$this->time = 15;
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