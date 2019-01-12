<?php

namespace tream;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;

class SizeShop extends PluginBase implements Listener {
	
    public function onEnable() {
    	@mkdir($this->getDataFolder());
        $this->setting = new Config ( $this->getDataFolder () . "setting.yml", Config::YAML,[
            "L" => 1000000,
            "S" => 1500000
        ]);
        $this->settingdb = $this->setting->getAll ();
        $this->data = new Config ( $this->getDataFolder () . "size.yml", Config::YAML);
        $this->db = $this->data->getAll ();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onJoin (PlayerJoinEvent $event){
    	$player = $event->getPlayer();
		$name = $player->getName();
		if(!isset( $this->db [strtolower($name)] ) ){
			$this->db [strtolower($name)] ["크기"] = 1;
			$this->onSave();
		}
		$player->setScale($this->db [strtolower($name)] ["크기"]);
	}
	public function modal() {
        $modal = [
            "type" => "modal",
            "title" => "§l§b[ §f크기상점 §b]§r§f",
            "content" => "§l§f포인트로 크기가 작아지거나 커져보세요!\n커지기 금액 : {$this->settingdb ["L"]}원\n작아지기 : {$this->settingdb ["S"]}원\n크기를 잠시동안 원상태로 바꿀수도 있습니다.\n[ 크기메뉴 ] -> [ 크기 저장하기 ]",
            "button1" => "§l§b[ §f현재크기 / 나가기 §b]§r§f",
            "button2" => "§l§b[ §f크기메뉴 / 나가기 §b]§r§f",		
            ];
		return json_encode ($modal);
	}
	public function custom() {
		 $custom = [ 
            "type" => "form",
            "title" => "§l§b[ §f크기상점 §b]§r§f",
            "content" => "§l§f편리한 크기상점", 
            "buttons" => [
            	[
                	"text" => "§l§b[ §f나가기 §b]§r§f",
                ],
                [
                    "text" => "§l§b[ §f커지기 -{$this->settingdb ["L"]}원 §b]§r§f",
                ],
                [
                    "text" => "§l§b[ §f작아지기 -{$this->settingdb ["S"]}원 §b]§r§f",
                ],
                [
                    "text" => "§l§b[ §f크기 저장하기 §b]§r§f",
                ],
                [
                	"text" => "§l§b[ §f크기 불러오기 §b]§r§f",
                ]
            ]
        ];
        return json_encode ($custom);
  }
  public function modalform(DataPacketReceiveEvent $event) {
		$pack = $event->getPacket ();
    	$player = $event->getPlayer();
		$pname = $player->getName();
		if ($pack instanceof ModalFormResponsePacket) {
			if($pack->formId == 1234){
			$name = json_decode ( $pack->formData, true );

			if($name) {
                $name = "true";
                $player->sendMessage("§l§b[ §f크기 §b]§r§f 나의 크기는 {$this->db [strtolower($pname)] ["크기"]}cm입니다.");
            }else{
                $name = "false";
                $pack = new ModalFormRequestPacket ();
                $pack->formId = 1235;
				$pack->formData = $this->custom();
				$player->dataPacket ($pack);
                }
            }
        }
    }
    public function customform(DataPacketReceiveEvent $event) {
		$pack = $event->getPacket ();
    	$player = $event->getPlayer();
		$pname = $player->getName();
		if ($pack instanceof ModalFormResponsePacket) {
			if($pack->formId == 1235){
				$name = json_decode ( $pack->formData, true );

				if($name == 0){
				}
				if($name == 1){
                if(EconomyAPI::getInstance()->mymoney($player) < $this->settingdb ["L"]){
                	$player->sendMessage("§l§b[ §f커지기 §b]§r§f 돈이 부족합니다.");
                	return true;
                }
                if($this->db [strtolower($pname)] ["크기"] > 1.4 ){
                    $player->sendMessage("§l§b[ §f커지기 §b]§r§f 현재 최대 성장판입니다.");
                    return true;
                }
                $player->sendMessage("§l§b[ §f커지기 §b]§r§f 돈 ".$this->settingdb ["L"]."원을 사용하고 1cm 자랐습니다.");
                $this->db [strtolower($pname)] ["크기"] += 0.1;
                $this->onSave();
                EconomyAPI::getInstance()->reducemoney($player, $this->settingdb ["L"]);
                $player->setScale($this->db [strtolower($pname)] ["크기"]);
				}
				if($name == 2){
                if(EconomyAPI::getInstance()->mymoney($player) < $this->settingdb ["S"]){
                	$player->sendMessage("§l§b[ §f작아지기 §b]§r§f 포인트가 부족합니다.");
                	return true;
                }
                if($this->db [strtolower($pname)] ["크기"] < 0.6){
                    $player->sendMessage("§l§b[ §f작아지기 §b]§r§f 더 작아지면 사라질껄요?");
                    return true;
                }
                $player->sendMessage("§l§b[ §f작아지기 §b]§r§f 돈 ".$this->settingdb ["S"]."원을 사용하고 1cm 줄었습니다.");
                $this->db [strtolower($pname)] ["크기"] -= 0.1;
                $this->onSave();
                EconomyAPI::getInstance()->reducemoney($player, $this->settingdb ["S"]);
                $player->setScale($this->db [strtolower($pname)] ["크기"]);
				}
				if($name == 3){
					$player->sendMessage("§l§b[ §f크기 §b]§r§f 현재 크기를 저장하고 임시적으로 기본크기를 불러왔습니다!");
					$player->sendMessage("§l§b[ §f크기 §b]§r§f 재접속 혹은 §l§b[ §f크기 불러오기 §b]§r§f 로 원래 크기로 돌아갈수있습니다!");
					$player->setScale(1);
				}
				if($name == 4){
					$player->sendMessage("§l§b[ §f크기 §b]§r§f 나의 크기로 돌아왔습니다!");
					$player->setScale($this->db [strtolower($pname)] ["크기"]);
                    $this->onSave();
				}
				}
			}
	}
    public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args) : bool {
		switch($cmd->getName()){
			case "크기상점":			    
				if($sender instanceof Player) {
                $p = new ModalFormRequestPacket ();
				$p->formId = 1234;
				$p->formData = $this->modal();
				$sender->dataPacket ($p);
				return true;
						}
				}
				return false;
    }
   public function onSave (){
   $this->data->setAll($this->db);
   $this->data->save();
   }
	
	
	
}
