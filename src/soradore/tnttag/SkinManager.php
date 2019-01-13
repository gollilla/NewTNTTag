<?php

namespace soradore\tnttag;
use pocketmine\entity\Skin;

class SkinManager{


    public $origin = [];
    public $data;

    public function __construct($data){
    	$this->data = $data;
    }

	public function setTnt($player){
		$skin = $player->getSkin();
		$this->origin[$player->getName()] = $skin->getSkinData();
		$path = $this->data->getDataFolder() . "test.png";
		$img = @imagecreatefrompng($path);
		$skinbytes = "";
		$s = (int)@getimagesize($path)[1];
		for($y = 0; $y < $s; $y++){
			for($x = 0; $x < 64; $x++){
				$colorat = @imagecolorat($img, $x, $y);
				$a = ((~((int)($colorat >> 24))) << 1) & 0xff;
				$r = ($colorat >> 16) & 0xff;
				$g = ($colorat >> 8) & 0xff;
				$b = $colorat & 0xff;
				$skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		@imagedestroy($img);
		$player->setSkin(new Skin($skin->getSkinId(), $skinbytes, "", "geometry.tnt", file_get_contents($this->data->getDataFolder() . "test2.json")));
		$player->sendSkin();
	}


	public function setOrigin($player){
		$name = $player->getName();
		if(isset($this->origin[$name])){
			$skin = $player->getSkin();
			$player->setSkin(new Skin($skin->getSkinId(), $this->origin[$name]));
			$player->sendSkin();
		}
	}
		
	
}
