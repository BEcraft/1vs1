<?php

namespace BEcraft\Minigame\task;

use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use BEcraft\Minigame\Main;
use pocketmine\utils\TextFormat as T;

class SignTask extends PluginTask{
	
	public function __construct(Main $main){
	parent::__construct($main);
	$this->plugin = $main;
	}
	
	public function onRun($tick){
	$world = $this->plugin->getServer()->getDefaultLevel();
	$tiles = $world->getTiles();
	foreach($tiles as $sign){
	if($sign instanceof Sign){
    $text = $sign->getText();
    $prefix = T::GRAY."[".T::YELLOW."Practice".T::GRAY."]".T::RESET; 
    if($text[0] == $prefix){
	$game = strtolower($text[1]);
	if($this->plugin->arenaExists($game)){
	$config = new Config($this->plugin->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
	$name = $config->get("Name");
	$level = $config->get("Level");
	if(empty($config->get("Level"))) return;
	$colores = array(T::RED, T::GREEN, T::YELLOW, T::GOLD, T::LIGHT_PURPLE);
	$rand1 = $colores[array_rand($colores)];
	$rand2 = $colores[array_rand($colores)];
	if($this->gamesCount($game) <= 1){
	$sign->setText($prefix, $name, $rand1.$level, $rand2.$this->gamesCount($game)."/2");
	}else if($this->gamesCount($game) >= 2){
	$sign->setText($prefix, $name, $rand1.$level, T::RED."Running");
	}
	}
	}
    }
	}
	}
	
	public function gamesCount($game){
	return count($this->plugin->games[$game]);
	}
	
	}