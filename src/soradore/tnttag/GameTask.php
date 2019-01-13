<?php 

namespace soradore\tnttag;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class GameTask extends Task {

    const FINISH = 30;

    public function __construct($plugin){
    	$this->second = self::FINISH;
    	$this->plugin = $plugin;
    }


	public function onRun(int $tick){
		$this->second = $this->second - 1;
		$players = $this->plugin->playerManager->getAllPlayers();
		$pad = str_repeat("   ",20);
		foreach($players as $player){
		    $player->sendPopup($pad . "§bラウンド    §6" . $this->plugin->getRound() . "\n" .
		    	             $pad . "§a生存者    §6" . $this->plugin->getAliveCount() . "\n" .
		    	             $pad . "§a爆発まで   §6" . $this->second . "s\n \n \n \n");
		}
		
		if($this->second == 0){
			$this->plugin->prepare();
			$this->getHandler()->cancel();
		}
	}
}