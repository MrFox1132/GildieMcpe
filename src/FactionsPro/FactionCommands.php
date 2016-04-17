<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

class FactionCommands {
	
	public $plugin;
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if($sender instanceof Player) {
			$player = $sender->getPlayer()->getName();
			if(strtolower($command->getName('f'))) {
				if(empty($args)) {
					$sender->sendMessage($this->plugin->formatMessage("Please use /f help for a list of commands"));
					return true;
				}
				if(count($args == 2)) {
					
					/////////////////////////////// CREATE ///////////////////////////////
					
					if($args[0] == "stworz") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /g stworz <nazwa gildi>"));
							return true;
						}
						if(!(ctype_alnum($args[1]))) {
							$sender->sendMessage($this->plugin->formatMessage("Mozna tylko uzywac liter oraz cyfr!"));
							return true;
						}
						if($this->plugin->isNameBanned($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Ta nazwa jest niedozwolona!"));
							return true;
						}
						if($this->plugin->factionExists($args[1]) == true ) {
							$sender->sendMessage($this->plugin->formatMessage("Nazwa gildi juz taka instnieje!"));
							return true;
						}
						if(strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
							$sender->sendMessage($this->plugin->formatMessage("Nazwa jest zbyt dluga. Sprobuj ponownie!"));
							return true;
						}
						if($this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage($this->plugin->formatMessage("Pierw musisz odejsc z gildi"));
							return true;
						} else {
							$factionName = $args[1];
							$player = strtolower($player);
							$rank = "Leader";
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", $player);
							$stmt->bindValue(":faction", $factionName);
							$stmt->bindValue(":rank", $rank);
							$result = $stmt->execute();
							if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($player);
							}
							$sender->sendMessage($this->plugin->formatMessage("Gildia zostala zalozona!", true));
							return true;
						}
					}
					
					/////////////////////////////// INVITE ///////////////////////////////
					
					if($args[0] == "dodaj") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /fg dodaj <gracz>"));
							return true;
						}
						if(!$this->plugin->isInFaction($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz byc w gildi by uzyc komendy"));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "dodaj")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie mass odpowiednich uprawnien by skorzystac z tej komendy!"));
							return true;
						}
						if( $this->plugin->isFactionFull($this->plugin->getPlayerFaction($player)) ) {
							$sender->sendMessage($this->plugin->formatMessage("Gildia jest pelna! Wywal kogos z gildi."));
							return true;
						}
						$invited = $this->plugin->getServer()->getPlayerExact($args[1]);
						if($this->plugin->isInFaction($invited) == true) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz jest obecnie w gildi"));
							return true;
						}
						if(!$invited instanceof Player) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz nie jest aktywny!"));
							return true;
						}
						$factionName = $this->plugin->getPlayerFaction($player);
						$invitedName = $invited->getName();
						$rank = "Członek";
							
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
						$stmt->bindValue(":player", strtolower($invitedName));
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":invitedby", $sender->getName());
						$stmt->bindValue(":timestamp", time());
						$result = $stmt->execute();

						$sender->sendMessage($this->plugin->formatMessage("$invitedName zostal zaproszony!", true));
						$invited->sendMessage($this->plugin->formatMessage("Zostales dodany do gildi $factionName. Wpisz '/g akceptuj' lub '/f odrzuc' do czatu, aby zaakceptować lub odrzucić!", true));
					}
					
					/////////////////////////////// LEADER ///////////////////////////////
					
					if($args[0] == "lider") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /g lider <gracz>"));
							return true;
						}
						if(!$this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz być w gildi aby skorzystać z tej komendy!"));
							return true;
						}
						if(!$this->plugin->isLeader($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz być liderem aby skorzystać z tej komendy"));
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Pierw dodaj gracza do gildi!"));
							return true;
						}		
						if(!$this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz nie jest aktywny!"));
							return true;
						}
							$factionName = $this->plugin->getPlayerFaction($player);
							$factionName = $this->plugin->getPlayerFaction($player);
	
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", $player);
							$stmt->bindValue(":faction", $factionName);
							$stmt->bindValue(":rank", "Member");
							$result = $stmt->execute();
	
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", strtolower($args[1]));
							$stmt->bindValue(":faction", $factionName);
							$stmt->bindValue(":rank", "Leader");
							$result = $stmt->execute();
	
	
							$sender->sendMessage($this->plugin->formatMessage("Jesteś już liderem!", true));
							$this->plugin->getServer()->getPlayer($args[1])->sendMessage($this->plugin->formatMessage("Jestes juz liderem/ka $factionName!",  true));
							if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($sender->getName());
								$this->plugin->updateTag($this->plugin->getServer()->getPlayer($args[1])->getName());
							}
						}
					
					/////////////////////////////// PROMOTE ///////////////////////////////
					
					if($args[0] == "awans") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /g awans <gracz>"));
							return true;
						}
						if(!$this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz byc w goldi by uzyc tej komendy!"));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "promote")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy"));
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz nie jest w tej gildi!"));
							return true;
						}
						if($this->plugin->isOfficer($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz jux kest oficerem"));
							return true;
						}
						$factionName = $this->plugin->getPlayerFaction($player);
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
						$stmt->bindValue(":player", strtolower($args[1]));
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":rank", "Officer");
						$result = $stmt->execute();
						$player = $args[1];
						$sender->sendMessage($this->plugin->formatMessage("" . $player . " zostal awansowany jako oficer!", true));
						if($player = $this->plugin->getServer()->getPlayer($args[1])) {
							$player->sendMessage($this->plugin->formatMessage("Jestes juz oficerem!", true));
						}
						if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($player->getName());
						}
					}
					
					/////////////////////////////// DEMOTE ///////////////////////////////
					
					if($args[0] == "zdegraduj") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /g zdegraduj <gracz>"));
							return true;
						}
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage($this->plugin->formatMessage("Muisz by pierw w gildi by skorzystac z tej komendy!"));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "demote")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy!"));
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz nie jest w tej gildi!"));
							return true;
						}
						if(!$this->plugin->isOfficer($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz jest juz czlonkiem"));
							return true;
						}
						$factionName = $this->plugin->getPlayerFaction($player);
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
						$stmt->bindValue(":player", strtolower($args[1]));
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":rank", "Member");
						$result = $stmt->execute();
						$player = $args[1];
						$sender->sendMessage($this->plugin->formatMessage("" . $player . " zostal juz czlonkiem.", true));
						
						if($player = $this->plugin->getServer()->getPlayer($args[1])) {
							$player->sendMessage($this->plugin->formatMessage("Zostałeś zdegradowany do czlonka gildi.", true));
						}
						if($this->plugin->prefs->get("FactionNametags")) {
							$this->plugin->updateTag($player->getName());
						}
					}
					
					/////////////////////////////// KICK ///////////////////////////////
					
					if($args[0] == "wyrzuc") {
						if(!isset($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Uzyj: /g wyrzuc <gracz>"));
							return true;
						}
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz byc pierw w gildi by skorzystac z tej komendy!"));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "kick")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy"));
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("Gracz nie jest w tej gildi"));
							return true;
						}
						$kicked = $this->plugin->getServer()->getPlayer($args[1]);
						$factionName = $this->plugin->getPlayerFaction($player);
						$this->plugin->db->query("DELETE FROM master WHERE player='$args[1]';");
						$sender->sendMessage($this->plugin->formatMessage("Zostal wywalony z $args[1]!", true));
						$players[] = $this->plugin->getServer()->getOnlinePlayers();
						if(in_array($args[1], $players) == true) {
							$this->plugin->getServer()->getPlayer($args[1])->sendMessage($this->plugin->formatMessage("Zostales wywalony/a z gildi $factionName!", true));
							if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($args[1]);
							}
							return true;
						}
					}
					
					/////////////////////////////// INFO ///////////////////////////////
					
					if(strtolower($args[0]) == 'info') {
						if(isset($args[1])) {
							if( !(ctype_alnum($args[1])) | !($this->plugin->factionExists($args[1]))) {
								$sender->sendMessage($this->plugin->formatMessage("Gildia nie istnieje"));
								return true;
							}
							$faction = strtolower($args[1]);
							$result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
							$array = $result->fetchArray(SQLITE3_ASSOC);
							$message = $array["message"];
							$leader = $this->plugin->getLeader($faction);
							$numPlayers = $this->plugin->getNumberOfPlayers($faction);
							$sender->sendMessage(TextFormat::BOLD . "-------------------------");
							$sender->sendMessage("$faction");
							$sender->sendMessage(TextFormat::BOLD . "Lider: " . TextFormat::RESET . "$leader");
							$sender->sendMessage(TextFormat::BOLD . "# Graczy: " . TextFormat::RESET . "$numPlayers");
							$sender->sendMessage(TextFormat::BOLD . "Motto: " . TextFormat::RESET . "$message");
							$sender->sendMessage(TextFormat::BOLD . "-------------------------");
						} else {
							$faction = $this->plugin->getPlayerFaction(strtolower($sender->getName()));
							$result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
							$array = $result->fetchArray(SQLITE3_ASSOC);
							$message = $array["message"];
							$leader = $this->plugin->getLeader($faction);
							$numPlayers = $this->plugin->getNumberOfPlayers($faction);
							$sender->sendMessage(TextFormat::BOLD . "-------------------------");
							$sender->sendMessage("$faction");
							$sender->sendMessage(TextFormat::BOLD . "Lider: " . TextFormat::RESET . "$leader");
							$sender->sendMessage(TextFormat::BOLD . "# Graczy: " . TextFormat::RESET . "$numPlayers");
							$sender->sendMessage(TextFormat::BOLD . "Motto: " . TextFormat::RESET . "$message");
							$sender->sendMessage(TextFormat::BOLD . "-------------------------");
						}
					}
					if(strtolower($args[0]) == "pomoc") {
						if(!isset($args[1]) || $args[1] == 1) {
							$sender->sendMessage(TextFormat::BLUE . "FactionsPro Help Page 1 of 3" . TextFormat::RED . "\n/f about\n/f accept\n/f claim\n/f create <name>\n/f del\n/f demote <player>\n/f deny");
							return true;
						}
						if($args[1] == 2) {
							$sender->sendMessage(TextFormat::BLUE . "FactionsPro Help Page 2 of 3" . TextFormat::RED . "\n/f home\n/f help <page>\n/f info\n/f info <faction>\n/f invite <player>\n/f kick <player>\n/f leader <player>\n/f leave");
							return true;
						} else {
							$sender->sendMessage(TextFormat::BLUE . "FactionsPro Help Page 3 of 3" . TextFormat::RED . "\n/f motd\n/f promote <player>\n/f sethome\n/f unclaim\n/f unsethome");
							return true;
						}
					}
				}
				if(count($args == 1)) {
					
					/////////////////////////////// CLAIM ///////////////////////////////
					
					if(strtolower($args[0]) == 'obszar') {
						if($this->plugin->prefs->get("ClaimingEnabled") == false) {
							$sender->sendMessage($this->plugin->formatMessage("Plots are not enabled on this server."));
							return true;
						}
						if(!$this->plugin->isInFaction($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz byc w gildi."));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "claim")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpiwiednich uprawnien by wpisac ta komende!"));
							return true;
						}
						if($this->plugin->inOwnPlot($sender)) {
							$sender->sendMessage($this->plugin->formatMessage("Twoja gildia jest gotowa na ten obszar."));
							return true;
						}
						$x = floor($sender->getX());
						$y = floor($sender->getY());
						$z = floor($sender->getZ());
						$faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
						if(!$this->plugin->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), $this->plugin->prefs->get("PlotSize"))) {
							return true;
						}
						$sender->sendMessage($this->plugin->formatMessage("Plot claimed.", true));
					}
					
					/////////////////////////////// UNCLAIM ///////////////////////////////
					
					if(strtolower($args[0]) == "innyobszar") {
						if($this->plugin->prefs->get("ClaimingEnabled") == false) {
							$sender->sendMessage($this->plugin->formatMessage("Plots are not enabled on this server."));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "unclaim")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by uzyc tej komendy"));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
						$sender->sendMessage($this->plugin->formatMessage("Plot unclaimed.", true));
					}
					
					/////////////////////////////// MOTD ///////////////////////////////
					
					if(strtolower($args[0]) == "motto") {
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz byc w gildi by uzyc tej komendy!"));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "motd")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy!"));
							return true;
						}
						$sender->sendMessage($this->plugin->formatMessage("Type your message in chat. It will not be visible to other players", true));
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO motdrcv (player, timestamp) VALUES (:player, :timestamp);");
						$stmt->bindValue(":player", strtolower($sender->getName()));
						$stmt->bindValue(":timestamp", time());
						$result = $stmt->execute();
					}
					
					/////////////////////////////// ACCEPT ///////////////////////////////
					
					if(strtolower($args[0]) == "akceptuje") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(empty($array) == true) {
							$sender->sendMessage($this->plugin->formatMessage("Nie zostales zaproszony do gildi"));
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if(($currentTime - $invitedTime) <= 60) { //This should be configurable
							$faction = $array["faction"];
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", strtolower($player));
							$stmt->bindValue(":faction", $faction);
							$stmt->bindValue(":rank", "Member");
							$result = $stmt->execute();
							$this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
							$sender->sendMessage($this->plugin->formatMessage("Wlasnie dolaczyles do gildi $faction!", true));
							if($this->plugin->getServer()->getPlayer($array["invitedby"])) {
								if($this->plugin->getServer()->getPlayer($array["invitedby"])) {
									$this->plugin->getServer()->getPlayer($array["invitedby"])->sendMessage($this->plugin->formatMessage("$player dolaczyl do gildi!", true));
								}
							}
							if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($sender->getName());
							}
						} else {
							$sender->sendMessage($this->plugin->formatMessage("Dodanie do gildi wygaslo!"));
							$this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
						}
					}
					
					/////////////////////////////// DENY ///////////////////////////////
					
					if(strtolower($args[0]) == "odrzuc") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(empty($array) == true) {
							$sender->sendMessage($this->plugin->formatMessage("Nie zostales zaproszony do gildi!"));
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if( ($currentTime - $invitedTime) <= 60 ) { //This should be configurable
							$this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
							$sender->sendMessage($this->plugin->formatMessage("Invite declined!", true));
							$this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage($this->plugin->formatMessage("$player odrzucil zaproszenie!"));
						} else {
							$sender->sendMessage($this->plugin->formatMessage("Dodanie do gildi wygaslo!"));
							$this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
						}
					}
					
					/////////////////////////////// DELETE ///////////////////////////////
					
					if(strtolower($args[0]) == "usun") {
						if($this->plugin->isInFaction($player) == true) {
							if($this->plugin->isLeader($player)) {
								$faction = $this->plugin->getPlayerFaction($player);
								$this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
								$sender->sendMessage($this->plugin->formatMessage("Gildia zostala rozwiazana!", true));
								if($this->plugin->prefs->get("FactionNametags")) {
									$this->plugin->updateTag($sender->getName());
								}
							} else {
								$sender->sendMessage($this->plugin->formatMessage("Nie jestes liderem!"));
							}
						} else {
							$sender->sendMessage($this->plugin->formatMessage("Nie jestes w gildi!"));
						}
					}
					
					/////////////////////////////// LEAVE ///////////////////////////////
					
					if(strtolower($args[0] == "odejsc")) {
						if($this->plugin->isLeader($player) == false) {
							$remove = $sender->getPlayer()->getNameTag();
							$faction = $this->plugin->getPlayerFaction($player);
							$name = $sender->getName();
							$this->plugin->db->query("DELETE FROM master WHERE player='$name';");
							$sender->sendMessage($this->plugin->formatMessage("Wlasnie odeszles z gildi $faction", true));
							if($this->plugin->prefs->get("FactionNametags")) {
								$this->plugin->updateTag($sender->getName());
							}
						} else {
							$sender->sendMessage($this->plugin->formatMessage("Musisz rozwaiazac gildie lub dac innemu lidera!"));
						}
					}
					
					/////////////////////////////// SETHOME ///////////////////////////////
					
					if(strtolower($args[0] == "ustawdom")) {
						if(!$this->plugin->isInFaction($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz miec pierw gildie."));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "sethome")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy"));
							return true;
						}
						$factionName = $this->plugin->getPlayerFaction($sender->getName());
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z, world) VALUES (:faction, :x, :y, :z, :world);");
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":x", $sender->getX());
						$stmt->bindValue(":y", $sender->getY());
						$stmt->bindValue(":z", $sender->getZ());
						$stmt->bindValue(":world", $sender->getLevel()->getName());
						$result = $stmt->execute();
						$sender->sendMessage($this->plugin->formatMessage("Dom dodany!", true));
					}
					
					/////////////////////////////// UNSETHOME ///////////////////////////////
						
					if(strtolower($args[0] == "usundom")) {
						if(!$this->plugin->isInFaction($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz miec pierw gildie."));
							return true;
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "unsethome")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowiednich uprawnien by skorzystac z tej komendy"));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
						$sender->sendMessage($this->plugin->formatMessage("Dom usunieto!", true));
					}
					
					/////////////////////////////// HOME ///////////////////////////////
						
					if(strtolower($args[0] == "dom")) {
						if(!$this->plugin->isInFaction($player)) {
							$sender->sendMessage($this->plugin->formatMessage("Musisz miec pierw gildie."));
						}
						if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "home")) {
							$sender->sendMessage($this->plugin->formatMessage("Nie masz odpowienich uprawnien by skorzytac z tej komendy"));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(!empty($array)) {
							$world = $this->plugin->getServer()->getLevelByName($array['world']);
+							$sender->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $world));
							$sender->sendMessage($this->plugin->formatMessage("Teleportacja do domu gildi.", true));
							return true;
						} else {
							$sender->sendMessage($this->plugin->formatMessage("Dom nie jest ustawiony."));
							}
						}
					
					/////////////////////////////// ABOUT ///////////////////////////////
					
					if(strtolower($args[0] == 'informacje')) {
						$sender->sendMessage(TextFormat::BLUE . "FactionsPro v1.4.0 BETA by " . TextFormat::BOLD . "PolandPEEdit" . TextFormat::RESET . TextFormat::BLUE . "" . TextFormat::ITALIC . "GitHub: https://github.com/PolandPEEdit");
					}
				}
			}
		} else {
			$this->plugin->getServer()->getLogger()->info($this->plugin->formatMessage("Prosze komendy wpisywac w grze! :)"));
		}
	}
}
