<?php

namespace CoinShop;

use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\item\Item;
use MixCoinSystem\MixCoinSystem;


class Main extends PluginBase implements Listener{


  

   public function onEnable(){
      $this->coin =  MixCoinSystem::getInstance();
      $this->getServer()->getPluginManager()->registerEvents($this,$this);
    
      if (!file_exists($this->getDataFolder())) {
       @mkdir($this->getDataFolder(), 0744, true);
      }
    
      $this->buyshop = new Config($this->getDataFolder(). "buyshop.yml", Config::YAML);
      $this->sellshop = new Config($this->getDataFolder(). "sellshop.yml", Config::YAML);

      if($this->getServer()->getPluginManager()->getPlugin("MixCoinSystem") == NULL){
          $this->getLogger()->error("MixCoinSystemが入れられていません。入れてきてください");                
          $this->getServer()->shutdown();
        }else{
        $this->getLogger()->notice(Textformat::AQUA."MixCoinSystemを確認しました");
        }
  }

  public function onJoin(PlayerJoinEvent $event){
      $this->allset();
        
        }

 public function sendForm(Player $player, $title, $come, $buttons, $id) {
      $pk = new ModalFormRequestPacket(); 
      $pk->formId = $id;
      $this->pdata[$pk->formId] = $player;
      $data = [ 
      'type'    => 'form', 
      'title'   => $title, 
      'content' => $come, 
      'buttons' => $buttons 
      ]; 
      $pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
      $player->dataPacket($pk);
      $this->lastFormData[$player->getName()] = $data;
      }

      public function sendCustom(Player $player, $title, $elements, $id) {
    
      $pk = new ModalFormRequestPacket(); 
      $pk->formId = $id;
      $this->pdata[$pk->formId] = $player;
      $data = [ 
      'type'    => 'custom_form', 
      'title'   => $title, 
      'content' => $elements
      ]; 
      $pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
      $player->dataPacket($pk);
      }

      public function onPrecessing(DataPacketReceiveEvent $event){

    $player = $event->getPlayer();
    $pk = $event->getPacket();
    $name = $player->getName();
    if($pk->getName() == "ModalFormResponsePacket"){
      $data = $pk->formData;
      if($data == "null\n"){
      }else{
          switch($pk->formId){

          case 20000;//購入
          $id = 0;
          $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
           $a = $this->buyshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $buttons[] = ['text' => "はい"];
          $buttons[] = ['text' => "いいえ"];
          $this->sendForm($player,"確認","本当に{$Con}を購入しますか？\nCoin必要枚数{$a["coin"]}",$buttons,20001);
        }
      }
        break;

          case 20001://確認
          if($data == 0){
            $this->buyshop2($player);
        }
        break;

        case 20002://確認
          if($data == 0){
          $this->buyshop2($player);
          }elseif($data == 1){
          $this->buyshop($player);
          }
          break;



          case 21001://変更の確認
          $data = json_decode($data,true);
          if($data[0] === ""){
            $this->error($player,1);
          }elseif($data[1] === ""){
            $this->error($player,1);
          }elseif($data[2] === ""){
            $this->error($player,1);
          }elseif($data[3] === ""){
            $this->error($player,1);
          }elseif($data[4] === ""){
            $this->error($player,1);
          }elseif(0 > $data[4]){
            $this->error($player,2);
          }else{
            $this->buyshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
            $this->buyshop->save();
            $this->allset();
          $buttons[] = ['text' => "続けて追加する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","追加できました",$buttons,21002);

        }
          break;


          case 21002://変更続けるかどうか
          if($data == 0){
          $this->SetItem($player);
        }
          break;


          case 22001://削除
          $id = 0;
          $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
            $a = $this->buyshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $buttons[] = ['text' => "はい"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"確認","本当に{$Con}を消しますか？",$buttons,22002);
          }
        }
          break;


          case 22002://はいを押したときの反応
          if($data == 0){
          $this->buyshop->remove($player->shop);
          $this->buyshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて削除する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"削除成功","削除しました",$buttons,22003);
          }
          break;


          case 22003://次の動作
          if($data == 0){
            $this->debuyshop($player);
          }
          break;


          case 23001://変更関連
          $id = 0;
          $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
          $a = $this->buyshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $elements[] = ['type' => "input",'text' => "表示名","placeholder" => "",'default' => $Con];
          $elements[] = ['type' => "input",'text' => "アイテムID","placeholder" => "",'default' => $a["id"]];
          $elements[] = ['type' => "input",'text' => "ダメージ値","placeholder" => "",'default' => $a["damage"]];
          $elements[] = ['type' => "input",'text' => "個数","placeholder" => "",'default' => $a["amount"]];
          $elements[] = ['type' => "input",'text' => "値段(coin)","placeholder" => "",'default' => $a["coin"]];
          $this->sendCustom($player,"打ち込んでください",$elements,23002);
          }
        }
            break;

            case 23002://設定変更
            $data = json_decode($data,true);
             if($data[0] === ""){
            $this->error($player,10);
          }elseif($data[1] === ""){
            $this->error($player,10);
          }elseif($data[2] === ""){
            $this->error($player,10);
          }elseif($data[3] === ""){
            $this->error($player,10);
          }elseif($data[4] === ""){
            $this->error($player,10);
          }elseif(0 > $data[4]){
            $this->error($player,11);
          }elseif($player->shop == $data[1]){
          $this->buyshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
          $this->buyshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて変更する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","変更できました",$buttons,23003);
          }else{
          $this->buyshop->remove($player->shop);
          $this->buyshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
          $this->buyshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて変更する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","変更できました",$buttons,23003);
          }
          break;

          case 23003://changebuyshop移動
          if($data == 0){
          $this->changebuyshop($player);
        }
          break;
//--------------------------------------------------------------------------------------------------ここからsellshop

          case 24000://売る
          $id = 0;
          $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
            $a = $this->sellshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $buttons[] = ['text' => "はい"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"確認","本当に{$Con}をCoinにしますか？\n必要アイテム数{$a["amount"]}",$buttons,24001);
          }
        }
        break;

          case 24001://変更
          if($data == 0){
            $this->sellshop2($player);
        }
        break;

          case 24002://確認
          if($data == 0){
          $this->sellshop2($player);
          }elseif($data == 1){
          $this->sellshop($player);
          }
          break;

          case 25000://追加
            $data = json_decode($data,true);
          if($data[0] === ""){
            $this->error($player,14);
          }elseif($data[1] === ""){
            $this->error($player,14);
          }elseif($data[2] === ""){
            $this->error($player,14);
          }elseif($data[3] === ""){
            $this->error($player,14);
          }elseif($data[4] === ""){
            $this->error($player,14);
          }elseif(0 > $data[4]){
            $this->error($player,11);
          }else{
          $this->sellshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
          $this->sellshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて追加する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","追加できました",$buttons,25001);
          }
          break;

          case 25001:
          if($data == 0){
          $this->SetItem2($player);
          }
          break;

          case 26000:
          $id = 0;
          $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
          $a = $this->sellshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $elements[] = ['type' => "input",'text' => "表示名","placeholder" => "",'default' => $Con];
          $elements[] = ['type' => "input",'text' => "アイテムID","placeholder" => "",'default' => $a["id"]];
          $elements[] = ['type' => "input",'text' => "ダメージ値","placeholder" => "",'default' => $a["damage"]];
          $elements[] = ['type' => "input",'text' => "個数","placeholder" => "",'default' => $a["amount"]];
          $elements[] = ['type' => "input",'text' => "値段(coin)","placeholder" => "",'default' => $a["coin"]];
          $this->sendCustom($player,"打ち込んでください",$elements,26001);
          }
        }
        break;

            case 26001://完全に設定
            $data = json_decode($data,true);
             if($data[0] === ""){
            $this->error($player,13);
          }elseif($data[1] === ""){
            $this->error($player,13);
          }elseif($data[2] === ""){
            $this->error($player,13);
          }elseif($data[3] === ""){
            $this->error($player,13);
          }elseif($data[4] === ""){
            $this->error($player,13);
          }elseif(0 > $data[4]){
            $this->error($player,15);
          }elseif($player->shop == $data[1]){
          $this->sellshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
          $this->sellhop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて変更する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","変更できました",$buttons,26002);
          }else{
          $this->sellshop->remove($player->shop);
          $this->sellshop->set($data[0],["id" => $data[1],"damage" => $data[2],"amount" => $data[3],"coin" => $data[4]]);
          $this->sellshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて変更する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功","変更できました",$buttons,26002);


          }
          break;

          case 26002:
          if($data == 0){
          $this->changesellshop($player);
        }
        break;


          case 27000://削除
          $id = 0;
          $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
            $a = $this->sellshop->get($Con);
          if(!($data == $a["number"])){
          $id++;
          }else{
          $player->shop = $Con;
          $buttons[] = ['text' => "はい"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"確認","本当に{$Con}を消しますか？",$buttons,27001);
          }
        }
          break;


          case 27001://はいを押したときの反応
          if($data == 0){
          $this->sellshop->remove($player->shop);
          $this->sellshop->save();
          $this->allset();
          $buttons[] = ['text' => "続けて削除する"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"削除成功","削除しました",$buttons,27002);
          }
          break;


          case 27002://次の動作
          if($data == 0){
            $this->desellshop($player);
          }
          break;




          case 99999:
          case 99998:
          if($data == 0){
          $this->SetItem($player);
          }
          break;

          case 99997:
          if($data == 0){
          $this->buyshop($player);
          }
          break;

          case 99990:
          if($data == 0){
          $this->sellshop($player);
          }
          break;

          case 99989:
          if($data == 0){
          $this->changesellshop($player);
          }
          break;


          }
        }
      }
    }

    public function allset(){
      $id = 0;
        $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
            $a = $this->buyshop->get($Con);
            $this->buyshop->set($Con,["id" => $a["id"],"damage" => $a["damage"],"amount" => $a["amount"],"coin" => $a["coin"],"number" => $id]);
            $this->buyshop->save();
            $id++;
            }
            $id = 0;
          $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
            $a = $this->sellshop->get($Con);
            $this->sellshop->set($Con,["id" => $a["id"],"damage" => $a["damage"],"amount" => $a["amount"],"coin" => $a["coin"],"number" => $id]);
            $this->sellshop->save();
            $id++;
           }
         }
         

    public function changebuyshop($player){
        $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
            $a = $this->buyshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
           $this->error($player,9);
           }else{
          $this->sendForm($player,"どれの内容を変更しますか？","",$buttons,23001);
           }
          }

    public function debuyshop($player){
          $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
             $a = $this->buyshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
           $this->error($player,6);
           }else{
          $this->sendForm($player,"どれを消しますか？","",$buttons,22001);
           }
          }


      public function buyshop($player){
        $item = $this->buyshop->getAll(true);
          foreach($item as $Con){
             $a = $this->buyshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
           $this->error($player,5);
          }else{
          $this->sendForm($player,"どれをCoinと交換しますか？","(Coin枚数)と[個数]",$buttons,20000);
        }
    }

      public function buyshop2($player){
          $a = $this->buyshop->get($player->shop);
          if($a["coin"] > $this->coin->GetCoin($player)){
          $this->error($player,3);
          }else{
          $this->coin->MinusCoin($player,$a["coin"]);
          $player->getInventory()->addItem(Item::get($a["id"],$a["damage"],$a["amount"]));
          $buttons[] = ['text' => "同じものを購入する"];
          $buttons[] = ['text' => "shopに戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功",$player->shop."とCoinを交換しました",$buttons,20002);
          }

    }

          public function sellshop($player){
        $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
          $a = $this->sellshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
          $this->error($player,11);
          }else{
          $this->sendForm($player,"どれをCoinにしますか？","(Coin枚数)と[個数]",$buttons,24000);
        }
    }


        public function sellshop2($player){
          $a = $this->sellshop->get($player->shop);
          if(!$player->getInventory()->contains(Item::get($a["id"],$a["damage"],$a["amount"]))){
          $this->error($player,12);
          }else{
          $this->coin->PlusCoin($player,$a["coin"]);
          $player->getInventory()->removeItem(Item::get($a["id"],$a["damage"],$a["amount"]));
          $buttons[] = ['text' => "同じものをCoinにする"];
          $buttons[] = ['text' => "sellshopに戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"成功",$player->shop."をCoinにしました",$buttons,24002);
          }
        }

        public function changesellshop($player){
        $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
            $a = $this->sellshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
           $this->error($player,9);
           }else{
          $this->sendForm($player,"どれの内容を変更しますか？","",$buttons,26000);
           }
          }

    public function desellshop($player){
          $item = $this->sellshop->getAll(true);
          foreach($item as $Con){
             $a = $this->sellshop->get($Con);
          $buttons[] = ['text' => $Con." (枚数:".$a["coin"].") [個数".$a["amount"]."]"];
          }
          if(!isset($buttons)){
           $this->error($player,6);
           }else{
          $this->sendForm($player,"どれを消しますか？","",$buttons,27000);
           }
          }


        public function SetItem($player){
          $elements[] = ['type' => "input",'text' => "表示名","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "アイテムID","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "ダメージ値","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "個数","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "値段(coin)","placeholder" => "",'default' => ""];
            $this->sendCustom($player,"打ち込んでください",$elements,21001);
          }

        public function SetItem2($player){
          $elements[] = ['type' => "input",'text' => "表示名","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "アイテムID","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "ダメージ値","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "個数","placeholder" => "",'default' => ""];
          $elements[] = ['type' => "input",'text' => "値段(coin)","placeholder" => "",'default' => ""];
            $this->sendCustom($player,"打ち込んでください",$elements,25000);
          }








      public function error($player,$id){
          if($id === 1){
          $buttons[] = ['text' => "入力画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"入力してない箇所があります\n※打ち直しになります",$buttons,99999);
        }elseif($id === 2){
          $buttons[] = ['text' => "入力画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"値段が0未満です\n※打ち直しになります",$buttons,99998);
        }elseif($id === 3){
          $buttons[] = ['text' => "buyshopに戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"手持ちのcoinが足りません",$buttons,99997);
        }elseif($id === 4){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"opではないため追加することはできません",$buttons,99996);
        }elseif($id === 5){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"shopに交換できるアイテムが存在しません\nopに頼んで追加してもらってください",$buttons,99995);
        }elseif($id === 6){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"shopに消せるものがありません",$buttons,99994);
        }elseif($id === 7){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"opではないため削除することはできません",$buttons,99993);
        }elseif($id === 8){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"opではないため内容を変更することはできません",$buttons,99992);
        }elseif($id === 9){
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"内容変更できるものがありません",$buttons,99991);
        }elseif($id === 10){
          $buttons[] = ['text' => "選択画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"入力していない箇所があります",$buttons,23003);
          }elseif($id === 11){
          $buttons[] = ['text' => "選択画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"値段が0未満です",$buttons,23003);
        }elseif($id === 12){
          $buttons[] = ['text' => "sellshopに戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"手持ちのアイテムが足りません",$buttons,99990);
          }elseif($id === 13){
          $buttons[] = ['text' => "選択画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"打っていない箇所があります",$buttons,99989);
        }elseif($id === 14){
          $buttons[] = ['text' => "入力画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"入力してない箇所があります\n※打ち直しになります",$buttons,25001);
        }elseif($id === 15){
          $buttons[] = ['text' => "選択画面に戻る"];
          $buttons[] = ['text' => "閉じる"];
          $this->sendForm($player,"error number.".$id,"値段が0未満です\n",$buttons,26002);
        }
        }

   public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        switch($command->getName()){

          case "bshop":
          if(!isset($args[0])){
          $this->buyshop($sender);
          break;
          }elseif($args[0] == "add" or $args[0] == "a"){
          if($sender->isOp()){
          $this->SetItem($sender,21001);
          }else{
          $this->error($sender,4);
          }
          }elseif($args[0] == "delete" or $args[0] == "d"){
          if($sender->isOp()){
          $this->debuyshop($sender);
          }else{
          $this->error($sender,7);
          }
          }elseif($args[0] == "change" or $args[0] == "c"){
          if($sender->isOp()){
          $this->changebuyshop($sender);
          }else{
          $this->error($sender,8);
          }
        }else{
          $this->buyshop($sender);
        }
          break;

          case "sshop":
          if(!isset($args[0])){
          $this->sellshop($sender);
          break;
          }elseif($args[0] == "add" or $args[0] == "a"){
          if($sender->isOp()){
          $this->SetItem2($sender);
          }else{
          $this->error($sender,4);
          }
          }elseif($args[0] == "delete" or $args[0] == "d"){
          if($sender->isOp()){
          $this->desellshop($sender);
          }else{
          $this->error($sender,7);
          }
          }elseif($args[0] == "change" or $args[0] == "c"){
          if($sender->isOp()){
          $this->changesellshop($sender);
          }else{
          $this->error($sender,8);
          }
        }else{
          $this->sellshop($sender);
        }






        }
        return true;
}
}
