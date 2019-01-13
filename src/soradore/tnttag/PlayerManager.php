<?php 

/**     _  ___ _ _ ____        ____  
 *     | |/ (_) | |  _ \__   _|  _ \ 
 *     | ' /| | | | |_) \ \ / / |_) |
 *     | . \| | | |  __/ \ V /|  __/ 
 *     |_|\_\_|_|_|_|     \_/ |_|    
 */ 
                                       
namespace soradore\tnttag;

use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\math\Vector3;

class PlayerManager {

	public function __construct($data){
		$this->data = $data;
        $this->players = [];
        $this->bomber = [];
        $this->alive = [];
        $this->skinManager = new SkinManager($data);
    }

	public function addPlayer($player){
        $this->players[] = $player;
        $this->alive[] = $player;
        $player->sendMessage("§b参加しました " . $this->getPlayerCount() . "人");
	}

	public function isPlayer($player){
		return in_array($player, $this->players, true);
	}

    public function isAlive($player){
        return in_array($player, $this->alive, true);
    }

    public function isBomber($player){
        return in_array($player, $this->bomber, true);
    }

	public function getPlayerCount(){
		return count($this->players);
	}

    public function getAliveCount(){
        return count($this->alive);
    }

    public function getAllAlives(){
        return $this->alive;
    }


    public function setBomber($player){
        if($this->isPlayer($player) && $this->isAlive($player)){
            $name = $player->getName();
            $this->bomber[] = $player;
            $this->skinManager->setTnt($player);
            $player->setNameTag("§c[Bomber] :" . $name);
            $player->sendTip("§cYou are IT\n\n\n");
        }
    }


    public function setBombers(array $players){
        foreach ($players as $player){
            $this->setBomber($player);
        }
    }


    public function killBombers(){
        $bombers = $this->bomber;
        foreach ($bombers as $bomber) {
            $level = $bomber->getLevel();
            $pos = new Vector3($bomber->x, $bomber->y, $bomber->z);
            $particle = new ExplodeParticle();
            $level->addParticle($pos,$particle);
            $this->removeAlive($bomber);
        }
        $this->bomber = [];
    }



	public function removePlayer($player){
		$key = array_search($player, $this->players, true);
		unset($this->players[$key]);
		array_values($this->players);
        $key = array_search($player, $this->alive, true);
        unset($this->alive[$key]);
        array_values($this->alive);
	}


    public function removeAlive($player){
        $key = array_search($player, $this->alive, true);
        unset($this->alive[$key]);
        array_values($this->alive);
    }


    public function removeBomber($player){
        $key = array_search($player, $this->bomber, true);
        unset($this->bomber[$key]);
        array_values($this->bomber);
        $player->setNameTag($player->getName());
        $this->skinManager->setOrigin($player);
    }

	public function getAllPlayers(){
		return $this->players;
	}

}