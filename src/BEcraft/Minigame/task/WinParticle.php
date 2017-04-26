<?php

namespace BEcraft\Minigame\task;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\level\particle\Particle;
use pocketmine\scheduler\PluginTask;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\PortalParticle;
use BEcraft\Minigame\Main;

class WinParticle extends PluginTask{
	
	public $time = 20;
	public $player;
	
	public function __construct(Main $main, Player $player){
	parent::__construct($main);
	$this->plugin = $main;
	$this->player = $player;
	}
	
	public function onRun($tick){
	$this->time--;
	$player = $this->player;
	$level = $player->getLevel();
	$server = Server::getInstance()->getDefaultLevel();
	if($level !== $server and $this->time > 0){
	$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
	}else
    if($level == $server and $this->time !== 0){
	$center = new Vector3($player->getX(), $player->getY()+3, $player->getZ());
	$radius = 1;
	$count = 100;
	$particles = array(new FlameParticle($center), new HeartParticle($center), new RedstoneParticle($center), new PortalParticle($center));
	$rand = $particles[array_rand($particles)];
	$particle = $rand;
	for($a = 0; $a < 100; $a++){
		$pitch = (mt_rand() / mt_getrandmax()-0.5)*M_PI;
			$yaw = mt_rand() / mt_getrandmax()*2*M_PI;
			$yi = -sin($pitch);
			$delta = cos($pitch);
			$xi = -sin($yaw)*$delta;
			$zi = cos($yaw)*$delta;
			$vector = new Vector3($xi, $yi, $zi);
			$pi = $center->add($vector->normalize()->multiply($radius));
			$particle->setComponents($pi->x, $pi->y+0.3, $pi->z);
			$player->getLevel()->addParticle($particle);
		}
		}else{
			if($this->time == 0){
				$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
				}
		}
	}
	
	}