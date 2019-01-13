<?php  

namespace soradore\tnttag;

use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\level\Position;

define("FILE_NAME", "setting.yml");

class DataManager {


	public function __construct($plugin){
        $dataFolder = $plugin->getDataFolder();
		if(!file_exists($dataFolder)){
			mkdir($dataFolder, 0744, true);
		}
		$this->data = new Config($dataFolder.FILE_NAME, Config::YAML, 
		                         [
                                  "Block_For_Join"=>["x"=>0, "y"=>4, "z"=>0, "world"=>"world"],
                                  "GAME_SPAWN"=>["x"=>0, "y"=>4, "z"=>0, "world"=>"world"],
		                         ]);
        $this->dataFolder = $dataFolder;
	}

	public function save(){
		$this->data->save();
	}

	public function getBlockForJoin(){
		$data = $this->data;
		$blockInfo = $data->get("Block_For_Join");
		$level = Server::getInstance()->getLevelByName($blockInfo["world"]);
        if($level == null){
        	return false;
        }
        return new Position($blockInfo["x"], $blockInfo["y"], $blockInfo["z"], $level);
	}

    public function setJoinBlock($block){
    	$this->data->set("Block_For_Join", ["x"=>$block->x, "y"=>$block->y, "z"=>$block->z, "world"=>$block->level->getName()]);
    	$this->save();
    }

    public function setGameSpawn($pos){
    	$this->data->set("GAME_SPAWN", ["x"=>$pos->x, "y"=>$pos->y, "z"=>$pos->z, "world"=>$pos->level->getName()]);
    	$this->save();
    }


    public function getGameSpawn(){
    	$data = $this->data->get("GAME_SPAWN");
    	return new Position($data["x"], $data["y"], $data["z"], Server::getInstance()->getLevelByName($data["world"]));
    }


    public function getDataFolder(){
        return $this->dataFolder;
    }

}