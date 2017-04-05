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
			if($text[0] == "practice"){
				$game = $text[1];
				if($this->plugin->arenaExists($game)){
					$config = new Config($this->plugin->getDataFolder()."Arenas/".$game.".yml", Config::YAML);
					$name = $config->get("Name");
					$level = $config->get("Level");
					$cantida = count(Server::getInstance()->getLevelByName($level)->getPlayers());
					$sign->setText($prefix, $name, T::AQUA.$level, T::YELLOW.$cantida."/2");
						 if($cantida >= 2){
							$sign->setText($prefix, $name, T::AQUA.$level, T::RED."Running");
							}
					}
				}
			}
		}
	
	}
	}