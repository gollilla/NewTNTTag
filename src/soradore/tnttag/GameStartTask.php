<?php 

namespace soradore\tnttag;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class GameStartTask extends Task {

    const FINISH = 20;

    public function __construct($plugin){
    	$this->second = self::FINISH;
    	$this->plugin = $plugin;
    }


	public function onRun(int $tick){
		$this->second = $this->second - 1;
		$players = $this->plugin->playerManager->getAllPlayers();
		foreach ($players as $player) {
			$player->sendTip("§b> ゲームスタートまで" . $this->second . " <\n\n");
		}
		if($this->second == 0){
			$this->plugin->start();
			$this->getHandler()->cancel();
		}
	}
}