<?php

namespace soradore\tnttag;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\Fireworks;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Facing;
use pocketmine\entity\Skin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;


class main extends PluginBase implements Listener{

    const MIN_GAME_START = 2; //ゲーム開始の最小人数

    const GAME_STATUS_PRE = 0; //ゲーム開始カウントなう
    const GAME_STATUS_NOW = 1; //プレイなう
    const GAME_STATUS_FIN = 2; //ゲームしてなかったら

    public $dataManager;
    public $playerManager;
    public $taskManager;
    public $game = self::GAME_STATUS_FIN;
    public $round = 1;
    public $task = [];

    

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->dataManager = new DataManager($this);
        $this->taskManager = new TaskManager($this->getScheduler());
        $this->iniGame();
    }


    public function iniGame(){
    	$this->playerManager = new PlayerManager($this->dataManager);
    }


    public function onInteract(PlayerInteractEvent $ev){
    	$player = $ev->getPlayer();
    	$block = $ev->getBlock();
    	$name = $player->getName();
    	if(isset($this->task[$name])){
    		if($this->task[$name] == "setblock"){
    			$this->dataManager->setJoinBlock($block);
    			$player->sendMessage("§b参加用ブロックを設定しました");
    			unset($this->task[$name]);
    			return false;
    		}
    	}
    	
    	if($this->isJoinBlock($block)){
    		if($this->game == self::GAME_STATUS_NOW){
    		        $player->sendMessage("§a> §b現在ゲーム中です。しばらくお待ちください");
    		        return false;
            }
    	    if(!$this->isPlayer($player)){ //参加用ブロックタッチかつ、現在ゲームに参加していなかったら
    		    $this->playerManager->addPlayer($player);
    		    $this->checkPlayers();//参加人数
    	    } 
       	}
    }


    /**
     * @param  Player $player
     * @return Bool
     */

    public function isBomber(Player $player){
    	return $this->playerManager->isBomber($player);
    }


    /**
     * @param  Player $player
     * @return Bool   
     */

    public function isPlayer(Player $player){
    	return $this->playerManager->isPlayer($player);
    }


    /**
     * @return int | player count
     */

    public function getPlayerCount(){
    	return $this->playerManager->getPlayerCount();
    }


    public function getAliveCount(){
    	return $this->playerManager->getAliveCount();
    }



    public function getRound(){
    	return $this->round;
    }


    /**
     * 
     */

    public function checkPlayers(){
    	if(self::MIN_GAME_START <= $this->getPlayerCount()){
    		if($this->game == self::GAME_STATUS_FIN){
    			$this->gameReady();//開始カウント
    		}
    		
    	}
    }

    /**
     * @param  Block $block
     * @return bool
     */
    public function isJoinBlock($block){
    	$data = $this->dataManager->getBlockForJoin();
    	$data = $data->level->getBlock($data);
        return ($data == $block);
    }



    public function gameReady(){
    	$this->game = self::GAME_STATUS_PRE;
    	$this->taskManager->submitTask(new GameStartTask($this), 20);
    }
    

    public function setBombers(){
    	$aliveCount = $this->getAliveCount();
    	$alives = $this->playerManager->getAllAlives();
    	if(2 < $aliveCount){
    		$num = floor($aliveCount*0.4);
    		$keys = array_rand($alives, $num);
    		foreach ($keys as $key) {
    			$this->playerManager->setBomber($alives[$key]);
    		}
    	}elseif($aliveCount==2){
    	    $key = array_rand($alives, 1);
    	    $this->playerManager->setBomber($alives[$key]);
    	}
    	return true;
    }


    public function onDamage(EntityDamageEvent $ev){
    	$entity = $ev->getEntity(); //殴られた
    	if(($ev instanceof EntityDamageByEntityEvent) && ($this->game == self::GAME_STATUS_NOW)){
    		$damager = $ev->getDamager(); //殴った
    		if(($entity instanceof Player) && ($damager instanceof Player)){
    			if($this->isPlayer($damager) && $this->isPlayer($entity)){
    				if($this->isBomber($damager) && !$this->isBomber($entity)){
    					$this->playerManager->removeBomber($damager);
    					$this->playerManager->setBomber($entity);
    				}
    		    }
    		}
    		$ev->setBaseDamage(0);
    		return true;
    	}
    	if($this->game == self::GAME_STATUS_FIN){
    		$ev->setCancelled();
    	}
    	
    }


    public function onQuit(PlayerQuitEvent $ev){
    	$player = $ev->getPlayer();
    	$this->playerManager->removePlayer($player);
    }


    public function onPlace(BlockPlaceEvent $ev){
    	$ev->setCancelled();
    }


    public function onDrop(PlayerDropItemEvent $ev){
    	$ev->setCancelled();
    }


    public function onJoin(PlayerJoinEvent $ev){
    	$player = $ev->getPlayer();
    	$player->getInventory()->clearAll();
    }

    public function onExhaust(PlayerExhaustEvent $ev){
    	$ev->setCancelled();
    }


    public function start(){
    	$this->game = self::GAME_STATUS_NOW;
    	$this->setBombers();
    	$this->taskManager->submitTask(new GameTask($this), 20);
    	$pos = $this->dataManager->getGameSpawn();
    	$players = $this->playerManager->getAllPlayers();
    	foreach($players as $player){
    		$player->teleport($pos);
    	}
    }


    public function prepare(){
    	$this->playerManager->killBombers();
    	if($this->getAliveCount() == 1){
    		$this->end();
    		return 0;
    	}
    	$this->taskManager->submitTask(new GamePrepareTask($this), 20);
    }


    public function nextRound(){
    	$this->setBombers();
    	$this->round = $this->round + 1;
    	if(6 <= $this->round){
    		$players = $this->playerManager->getAllAlives();
    		$pos = $this->dataManager->getGameSpawn();
    		foreach($players as $player){
    			$player->teleport($pos);
    		}
    	}
    }


    public function end(){
    	$winners = $this->playerManager->getAllAlives();
    	foreach ($winners as $winner) {
    		if($winner instanceof Player){
    	        $this->playerManager->sendMessage("§a勝者 : " . $winner->getName());
    	        $winner->setNameTag($winner->getName());
    	        $winner->teleport($winner->getLevel()->getSafeSpawn());
    	        $this->playerManager->skinManager->setOrigin($winner);
    	        $this->game = self::GAME_STATUS_FIN;
    	        $this->iniGame();
    	        break;
    	    }/*else{
    		    var_dump($winner);
    	    }*/
    	}
    	
    }


    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args): bool{
    	switch($cmd->getName()){
        	case 'setjoin':
        		if($sender instanceof Player){
        			$name = $sender->getName();
        			$this->task[$name] = "setblock";
        			$sender->sendMessage("§6> 設定したいブロックをタッチしてください");
        		}
        		break;
        	case 'setpos':
        	    if($sender instanceof Player){
        	    	$this->dataManager->setGameSpawn($sender);
        	    	$sender->sendMessage("§bSpawnを設定しました");
        	    }
        	    break;
        }
        return true;
    }
}


    

