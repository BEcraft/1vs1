<?php

namespace BEcraft\Minigame\task;

use pocketmine\scheduler\PluginTask;
use BEcraft\Minigame\Main;
use pocketmine\Server;
use pocketmine\entity\Effect;
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
	$messages = new Config($this->plugin->getDataFolder()."Messages.yml", Config::YAML);
	$name = $config->get("Name");
	$level = $config->get("Level");
	/*
	 * =======================
	 * WAITING STATUS
	 *=======================
	 */
	if($this->gamesCount($game) == 1 and $this->status == "waiting"){
	$config->set("Status", "waiting");
	$config->save();
	$player = $this->plugin->games[$game][0];
	$popup = $messages->get("waiting_popup");
	$popup = str_replace("{players}", $this->gamesCount($game), $popup);
	$player->sendPopup($popup);
    $blind = Effect::getEffect(15);
	$blind->setDuration(9999);
	$blind->setAmplifier(10);
	$blind->setVisible(false);
	$player->addEffect($blind);
	}
	/*
	 * =======================
	 * START GAME
	 *=======================
	 */
    else
    if($this->gamesCount($game) == 2){
	$this->time--;
	$this->status = "running";
	$config->set("Status", "running");
	$config->save();
    $player1 = $this->plugin->games[$game][0];
    $player2 = $this->plugin->games[$game][1];
    $player1->sendPopup(T::YELLOW."Time: ".T::GOLD.$this->getTime($this->time).T::GRAY." || ".T::YELLOW."Oponent: ".T::GOLD.$player2->getName());
    $player2->sendPopup(T::YELLOW."Time: ".T::GOLD.$this->getTime($this->time).T::GRAY." || ".T::YELLOW."Openent: ".T::GOLD.$player1->getName());
    /*
	 * =======================
	 * REMOVE ALL
	 *=======================
	 */
    if($this->time == 499){
    $player1->sendMessage($messages->get("game_started"));
	$player1->removeAllEffects();
	unset($this->plugin->move[$player1->getName()]);
	
	$player2->sendMessage($messages->get("game_started"));
	$player2->removeAllEffects();
	unset($this->plugin->move[$player2->getName()]);
	}
	}
	/*
	 * =======================
	 * WIN
	 *=======================
	 */
    else 
    if($this->gamesCount($game) == 1 and $this->status == "running" and $this->time > 0){
	$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
	foreach($this->plugin->games[$game] as $player){
	$player->getInventory()->clearAll();
	$player->setHealth(20);
	$win = $messages->get("win_message");
	$win = str_replace(["{player}", "{arena}"], [$player->getName(), $game], $win);
	Server::getInstance()->broadcastMessage($win);
	unset($this->plugin->playing[$player->getName()]);
	$this->plugin->deletePlayer($player);
	$this->plugin->games[$game] = [];
	$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
	$this->plugin->WinTask($player);
	$this->status = "waiting";
	$config->set("Status", "waiting");
	$config->save();
	$this->time = 500;
	}
	}
	/*
	 * =======================
	 * STOP GAME IF THERE IS NOT PLAYERS
	 *=======================
	 */
    else 
    if($this->gamesCount($game) == 0){
	$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
	}
	/*
	 * =======================
	 * TIME OVER 
	 *=======================
	 */
    else 
    if($this->gamesCount($game) == 2 and $this->time < 1 and $this->status == "running"){
	$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
	 /*
	 * =======================
	 * GET FIRST PLAYER
	 *=======================
	 */
	$player1 = $this->plugin->games[$game][0];
	$player1->getInventory()->clearAll();
	$player1->setHealth(20);
	unset($this->plugin->playing[$player1->getName()]);
	$this->plugin->removePlayer($player1);
	$player1->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	$nowin = $messages->get("no_win");
	$nowin = str_replace("{arena}", $game, $nowin);
	Server::getInstance()->broadcastMessage($nowin);
	$player1->sendMessage($messages->get("time_over"));
	/*
	 * =======================
	 * GET SECOND PLAYER
	 *=======================
	 */
	$player2 = $this->plugin->games[$game][1];
	$player2->getInventory()->clearAll();
	$player2->setHealth(20);
	unset($this->plugin->playing[$player2->getName()]);
	$this->plugin->removePlayer($player2);
	$player2->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	$player2->sendMessage($messages->get("time_over"));
	
	$this->status = "waiting";
	$config->set("Status", "waiting");
	$config->save();
	$this->time = 500;
	}
	}
	}
	
	/*
	 * =======================
	 * GET COUNT OF PLAYER IN ARENA
	 *=======================
	 */
	public function gamesCount($game){
	return count($this->plugin->games[$game]);
	}
	
    }