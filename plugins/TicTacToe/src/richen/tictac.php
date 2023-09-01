<?php declare(strict_types=1);

namespace richen;

class tictac extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener {
    public $prefix = '§8[§6Крестики§7-§eНолики§8]§r';
    public $messages = [
        "start"             => "§aИгра началась",
        "end"               => "§6Игра завершена",
        "time-end"          => "§6У игроков вышло время",
        "already-started"   => "§6Игра уже идёт",
        "not-winners"       => "§6В игре нет победителей! Ничья",
        "already-joined"    => "§cВы уже присоединились к игре",
        "tip-countdown"     => "§6Игра идёт §e%s §6сек.",
        "cant-step-here"    => "§cВы не можете сходить сюда, выберите другое место",
        "step-success"      => "§eИгрок §c%s §eсделал ход, теперь очередь игрока §a%s",
        "cant-step"         => "§cСейчас не ваша очередь",
        "not-player"        => "§cВы не участвуете в текущей игре",
        "refil"             => "§eПодождите немного! Происходят §dмагические §eпроцессы",
        "player-quit"       => "§cИгрок §6%s §cвышел с сервера",
        "need-two-players"  => "§6Для начала игры нужно §e2 игрока",
        "joined"            => "§eВы присоединились к игре §a%s§7/§62",
        "loading"           => "§eПодождите, игра загружается",
        "first-step"        => "§eПервым ходит игрок §b%s",
        "winner"            => "§aИгрок §e%s §aпобедил игрока §c%s §eв Крестики-Нолики",
        "waiting"           => "§6Ожидание второго игрока %s...",
        "diamonds-popup"    => "§3[§bRich§eGames§3] §fДобыто алмазов: §e%s шт.",
        "diamonds-get"      => "§3[§bRich§eGames§3] §fВы добыли алмаз: §e%s",
        "diamonds-result"   => "§3[§bRich§eGames§3] §fВы успели добыть: §e%s алмазов §7(§a+%s\$§7)",
        "diamonds-stats"    => "§3[§bRich§eGames§3] §fВаша статистика по добыче §bалмазов§f:\n§7* §fПоследний результат: §e%s\n§7* §fДобыто алмазов §7(и валюты)§f: §a%s$\n§7* §fРекорд: §e%s §bалмазов\n§7* §fВы сыграли: §e%s раз\n§7* §fВ среднем добываете: §e%s шт.",
        "tictac-stats"      => "Вы сыграли в крестики нолики: %s раз\n§7* §fПобед: §a%s§7/ §fПоражений: §6%s\n§7* §fВы в %s §fна §a%s$",
        "no-money"          => "§cНедостаточно средств! §6Чтобы принять участие необходимо: §2%s$"
    ];
    public array $coordinates = [
        [215, 71, 905], [214, 71, 905], [213, 71, 905],
        [215, 70, 905], [214, 70, 905], [213, 70, 905],
        [215, 69, 905], [214, 69, 905], [213, 69, 905]
    ];
    public int $price = 100;
    public array $tile = [];
    public int $gameCountdown = 60;
    public bool $gameStarted = false;
    public array $gamePlayers = []; 
    public ?\pocketmine\Player $currentPlayer = null;
    public ?\pocketmine\math\Vector3 $button = null;
    public ?\onebone\economyapi\EconomyAPI $economy = null;
    public ?int $diamonds = null;
    public ?int $diamondIndex = null;

    const N = 'n';
    const X = 'x';
    const O = 'o';
    const G = 'g';
    const D = 'd';
    public array $items = [];
    public array $sounds = [];
    public ?\pocketmine\utils\Config $bestconfig = null;
    public ?\pocketmine\utils\Config $tictacconfig = null;

    public function onEnable()
    {
        $defaultLevel = $this->getServer()->getDefaultLevel();

        $this->economy = \onebone\economyapi\EconomyAPI::getInstance();

        if (!$this->economy->isEnabled()) {
            $this->getLogger()->error('§6Плагин экономики EconomyAPI не запущен');
            $hasError = true;
        }

        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }

        $this->bestconfig = new \pocketmine\utils\Config($this->getDataFolder() . 'bestscore.yml', \pocketmine\utils\Config::YAML);
        $this->tictacconfig = new \pocketmine\utils\Config($this->getDataFolder() . 'tictacscore.yml', \pocketmine\utils\Config::YAML);

        $this->button = $this->getVector3(212, 69, 905);

        $this->items = [
            'n' => \pocketmine\item\Item::get(0, 0), // пустота во время начала игры
            'x' => \pocketmine\item\Item::get(30, 0), // крестик
            'o' => \pocketmine\item\Item::get(378, 0), // нолик
            'g' => \pocketmine\item\Item::get(266, 1), // если игра не запущена 
            'd' => \pocketmine\item\Item::get(264, 0) // алмаз
        ];

        $hasError = false;

        try {
            foreach ($this->coordinates as $index => $coord) {
                $tile = $defaultLevel->getTile($this->getVector3($coord[0], $coord[1], $coord[2]));
                if ($tile && $tile instanceof \pocketmine\tile\ItemFrame) {
                    $this->tile[$index] = $tile;
                    $this->setItem($tile, $this->items[self::G]);
                } else {
                    $this->getLogger()->error('§6На координатах §c' . $coord[0] . ' ' . $coord[1] . ' ' . $coord[2] . ' §6нету рамки');
                    $hasError = true;
                }
            }
        } catch (\Exception $err) {
            $this->getServer()->getLogger()->error($err->getMessage());
        }

        if($hasError) {
            $this->setEnabled(false);
        } else {
            $this->getServer()->getPluginManager()->registerEvents($this, $this);
            $this->refil();
        }
    }

    public function getScoreTicTac(string $name)
    {
        $name = mb_strtolower($name);
        if ($this->tictacconfig->exists($name)) {
            $data = json_decode($this->bestconfig->get($name), true);
        } else {
            $data = [
                'amount' => 0,
                'win' => 0,
                'lose' => 0,
                'attempt' => 0
            ];
        }

        return $data;
    }

    public function addScoreTicTac(string $name, int $money, bool $win)
    {
        $name = mb_strtolower($name);
        $data = $this->getScoreTicTac($name);
        $newData = [
            'amount' => $win ? $data['amount'] + $money : $data['amount'] - $money,
            'win' => $data['win'] + (int) $win,
            'lose' => $data['lose'] + (int) $win,
            'attempt' => $data['attempt'] + 1
        ];

        $this->bestconfig->set($name, json_encode($newData));
        $this->bestconfig->save();
    }

    public function getScore(string $name)
    {
        $name = mb_strtolower($name);
        if ($this->bestconfig->exists($name)) {
            $data = json_decode($this->bestconfig->get($name), true);
        } else {
            $data = [
                'last' => 0,
                'amount' => 0,
                'best' => 0,
                'attempt' => 0
            ];
        }

        return $data;
    }

    public function addScore(string $name, int $newscore)
    {
        $name = mb_strtolower($name);
        $data = $this->getScore($name);
        $newData = [
            'last' => $newscore,
            'amount' => $data['amount'] + $newscore,
            'best' => $data['best'] < $newscore ? $newscore : $data['best'],
            'attempt' => $data['attempt'] + 1
        ];

        $this->bestconfig->set($name, json_encode($newData));
        $this->bestconfig->save();
    }

    public function sendSound(\pocketmine\math\Vector3 $pos, string $sound)
    {
        $sound = '\pocketmine\\level\\sound\\' . $sound;
        if (class_exists($sound)) {
            $this->getServer()->getDefaultLevel()->addSound(new $sound($pos));
        }
    }

    public function getVector3(int $x, int $y, int $z, ?\pocketmine\level\Level $level = null)
    {
        if($level) {
            return new \pocketmine\level\Position($x, $y, $z, $level);
        } else {
            return new \pocketmine\math\Vector3($x, $y, $z);
        }
    }

    public function isPlayer(\pocketmine\Player $player)
    {
        foreach ($this->gamePlayers as $gamePlayer) {
            if ($gamePlayer->getName() === $player->getName()) {
                return true;
            }
        }
        return false;
    }

    public function onQuit(\pocketmine\event\player\PlayerQuitEvent $ev)
    {
        $player = $ev->getPlayer();

        $gamePlayers = $this->gamePlayers;

        if (in_array($player, $gamePlayers)) {
            unset($gamePlayers[array_search($player, $gamePlayers)]);
            $this->sendMessage(sprintf($this->messages['player-quit'], $player->getName()));
        }

        $this->gamePlayers = [];
        foreach ($gamePlayers as $pl) {
            $this->gamePlayers[] = $pl;
        }
    }

    public function joinGame(\pocketmine\Player $player)
    {
        if ($this->gameStarted) {
            return $this->messages['already-started'];
        }

        if ($this->refil) {
            return $this->messages['refil'];
        }

        if ($this->isPlayer($player)) {
            return $this->messages['already-joined'];
        }

        if ($this->economy->myMoney($player) < $this->price) {
            return sprintf($this->messages['no-money'], $this->price);
        }

        $this->gamePlayers[] = $player;

        $player->sendMessage($this->prefix . ' ' . sprintf($this->messages['joined'], count($this->gamePlayers)));

        if (count($this->gamePlayers) === 2) {
            $this->startGame();
        } else {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends \pocketmine\scheduler\Task
            {
                public tictac $pl;
                public int $countdown = 30;
                
                public function __construct(tictac $pl) {
                    $this->pl = $pl;
                }
                
                public function onRun($currentTick) {
                    if ($this->pl->gameStarted || count($this->pl->gamePlayers) === 2 || !count($this->pl->gamePlayers) || $this->pl->refil) {
                        $this->getHandler()->cancel();

                        return;
                    }
                    
                    if ($this->countdown > 0) {
                        $this->pl->sendMessage(sprintf($this->pl->messages['waiting'], $this->countdown), true);
                        $this->pl->gamePlayers[0]->sendPopup(sprintf($this->pl->messages['diamonds-popup'], $this->pl->diamonds));
                    } else {
                        $player = $this->pl->gamePlayers[0];
                        $this->pl->economy->addMoney($player, $this->pl->diamonds);
                        $player->sendMessage(sprintf($this->pl->messages['diamonds-result'], $this->pl->diamonds, $this->pl->diamonds));
                        $lastScore = $this->pl->getScore($player->getName())['last'];
                        $this->pl->addScore($player->getName(), $this->pl->diamonds);
                        $score = $this->pl->getScore($player->getName());
                        $avg = floor($score['amount'] / $score['attempt']);
                        $player->sendMessage(sprintf($this->pl->messages['diamonds-stats'], $lastScore, $score['amount'], $score['best'], $score['attempt'], $avg));
                        $this->pl->diamonds = 0;
                        $this->pl->setItem($this->pl->tile[$this->pl->diamondIndex], $this->pl->items[tictac::G]);
                        $this->pl->diamondIndex = 0;
                        $this->pl->gamePlayers = [];
                        $this->getHandler()->cancel();
                    }

                    $this->countdown--;
                }
            }, 20);

            $this->placeDiamond();

            return $this->messages['need-two-players'];
        }
    }

    public function placeDiamond()
    {
        $index = array_rand($this->tile);
        $tile = $this->tile[$index];
        $this->diamondIndex = $index;

        if (!$this->diamonds) {
            $this->diamonds = 0;
        }

        if ($tile instanceof \pocketmine\tile\ItemFrame) {
            $this->setItem($tile, $this->items[self::D]);
        }
    }

    public function sendMessage(string $message, bool $tip = false, bool $prefix = true)
    {
        $method = $tip ? 'sendTip' : 'sendMessage';
        foreach ($this->gamePlayers as $player) {
            $player->$method(($prefix ? $this->prefix . ' ' : '') . $message);
        }
    }

    public function startGame()
    {
        if (count($this->gamePlayers) === 2 && !$this->gameStarted) {
            $this->gameStarted = true;
            $this->refil(true);

            shuffle($this->gamePlayers);
            
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends \pocketmine\scheduler\Task {
                public tictac $pl;

                public int $countdown = 60;
                
                public function __construct(tictac $pl) {
                    $this->pl = $pl;
                    $this->countdown = $pl->gameCountdown + 10;
                }
                
                public function onRun($currentTick) {
                    $this->countdown--;

                    if ($this->countdown === 0) {
                        $this->pl->getServer()->broadcastMessage($this->pl->prefix . ' ' . $this->pl->messages['time-end']);
                        $this->pl->getServer()->broadcastMessage($this->pl->prefix . ' ' . $this->pl->messages['not-winners']);
                        $this->pl->stopGame();
                        $this->getHandler()->cancel();

                        return;
                    }

                    if ($this->pl->refil) {
                        $this->pl->sendMessage($this->pl->messages['loading'], true);

                        return;
                    }

                    if (!$this->pl->gameStarted) {
                        $this->getHandler()->cancel();

                        return;
                    } else {
                        $this->pl->sendMessage(sprintf($this->pl->messages['tip-countdown'], $this->countdown), true);
                    }

                    foreach ($this->pl->gamePlayers as $player) {
                        if (!$player->isOnline()) {
                            $this->pl->stopGame();
                            $this->getHandler()->cancel();

                            return;
                        }
                    }

                    if (!$this->pl->currentPlayer) {
                        $this->pl->currentPlayer = $this->pl->gamePlayers[0];
                        $this->pl->sendMessage(sprintf($this->pl->messages['first-step'], $this->pl->currentPlayer->getName()));
                        $this->pl->sendSound($this->pl->currentPlayer->asVector3(), 'ExpPickupSound');
                    }
                }
            }, 20);
        }
    }

    public function setItem(\pocketmine\tile\ItemFrame $tile, \pocketmine\item\Item $item)
    {
        $enchantment = \pocketmine\item\enchantment\Enchantment::getEnchantment(0);
        $enchantment->setLevel(1);
        $item->addEnchantment($enchantment);
        $tile->setItem($item);
        $this->sendSound($tile->asVector3(), 'ExpPickupSound');
    }

    public function step(\pocketmine\Player $player, \pocketmine\tile\ItemFrame $tile)
    {
        if ($this->gameStarted && !$this->isPlayer($player)) {
            return $player->sendTip($this->messages['already-started']);
        }

        if ($this->refil) {
            return $player->sendTip($this->messages['refil']);
        }

        if (!$this->isPlayer($player)) {
            return $player->sendTip($this->messages['not-player']);
        }

        if ($this->currentPlayer !== $player) {
            return $player->sendTip($this->messages['cant-step']);
        }

        $secondPlayer = $this->gamePlayers[0]->getName() === $player->getName() ? $this->gamePlayers[1] : $this->gamePlayers[0];
        
        switch ($tile->getItem()->getId()) {
            case $this->items[self::N]->getId():
                $this->sendMessage(sprintf($this->messages['step-success'], $player->getName(), $secondPlayer->getName()));
                $this->setItem($tile, array_search($player->getName(), [$this->gamePlayers[0]->getName(), $this->gamePlayers[1]->getName()]) === 0 ? $this->items[self::X] : $this->items[self::O]);
                $this->checkWinner();
                $this->currentPlayer = $secondPlayer;
                $this->sendSound($player->asVector3(), 'MilkSound');

                break;
            
            case $this->items[self::X]->getId():
            case $this->items[self::O]->getId():
                $player->sendMessage($this->prefix . ' ' . $this->messages['cant-step-here']);
                $this->sendSound($player->asVector3(), 'AnvilSound');

                break;
            
            default:

                break;
        }
    }

    public function getTileAtPos(\pocketmine\math\Vector3 $pos): ?\pocketmine\tile\ItemFrame
    {
        foreach ($this->tile as $index => $tile)
        {
            $vector3 = $tile->asVector3();

            if ($vector3->equals($pos)) {
                return $tile;
            }
        }

        return null;
    }

    public function onBlock(\pocketmine\event\block\ItemFrameDropItemEvent $ev)
    {
        $pos = $ev->getBlock()->asVector3();

        if ($this->getTileAtPos($pos)) {
            $ev->setCancelled();
        }
    }

    public function onTap(\pocketmine\event\player\PlayerInteractEvent $ev)
    {
        $player = $ev->getPlayer();
        $pos = $ev->getBlock()->asVector3();

        if ($this->button->equals($pos)) {
            $player->sendMessage($this->prefix . ' ' . $this->joinGame($player));
        }
        else {
            if ($tile = $this->getTileAtPos($pos)) {
                $ev->setCancelled();
                if ($tile instanceof \pocketmine\tile\ItemFrame && $tile->getItem()->getId() === $this->items[self::D]->getId()) {
                    $this->setItem($tile, $this->items[self::G]);
                    $this->placeDiamond();
                    $this->diamonds++;
                    $this->sendMessage(sprintf($this->messages['diamonds-get'], $this->diamonds, false), true);
                } else {
                    $this->step($player, $tile);
                }
            }
        }
    }
    
    public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $ev)
    {
        $pos = $ev->getBlock()->asVector3();
        foreach ($this->tile as $index => $tile) {
            $vector3 = $tile->asVector3();
            if ($vector3->equals($pos)) {
                $ev->setCancelled();

                break;
            }
        }
    }

    public function checkWinner() {
        $linesToCheck = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // горизонтальные линии
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // вертикальные линии
            [0, 4, 8], [2, 4, 6],            // диагонали
        ];

        if ($this->areAllTilesFilled()) {
            $this->getServer()->broadcastMessage($this->prefix . ' ' . $this->messages['end']);
            $this->getServer()->broadcastMessage($this->prefix . ' ' . $this->messages['not-winners']);
            $this->sendSound($this->tile[0]->asVector3(), 'FizzSound');
            $this->stopGame();

            return;
        }
    
        foreach ($linesToCheck as $line) {
            [$index1, $index2, $index3] = $line;
    
            $tile1 = $this->tile[$index1];
            $tile2 = $this->tile[$index2];
            $tile3 = $this->tile[$index3];

            if ($tile1->getItem()->getId() === $this->items[self::N]->getId() || $tile2->getItem()->getId() === $this->items[self::N]->getId() || $tile3->getItem()->getId() === $this->items[self::N]) {
                continue;
            } else {
                if ($this->isTileSame($tile1, $tile2, $tile3)) {
                    $winner = array_search($this->currentPlayer, $this->gamePlayers);

                    $player1 = $this->gamePlayers[$winner];
                    $player2 = $this->gamePlayers[$winner === 0 ? 1 : 0];

                    $this->getServer()->broadcastMessage($this->prefix . ' ' . $this->messages['end']);
                    $this->getServer()->broadcastMessage($this->prefix . ' ' . sprintf($this->messages['winner'], $player1->getName(), $player2->getName()));

                    $money = $this->price;

                    $this->addScoreTicTac($player1->getName(), $money, true);
                    $this->addScoreTicTac($player2->getName(), $money, false);

                    $this->economy->addMoney($player1, $money);
                    $this->economy->reduceMoney($player2, $money);

                    $stats1 = $this->getScoreTicTac($player1->getName());
                    $stats2 = $this->getScoreTicTac($player2->getName());

                    $status1 = $stats1['amount'] >= 0 ? '§aплюсе' : '§cминусе';
                    $status2 = $stats2['amount'] >= 0 ? '§aплюсе' : '§cминусе';

                    $player1->sendMessage($this->prefix . ' ' . sprintf($this->messages['tictac-stats'], $stats1['attempt'], $stats1['win'], $stats1['lose'], $status1, $stats1['money']));
                    $player2->sendMessage($this->prefix . ' ' . sprintf($this->messages['tictac-stats'], $stats2['attempt'], $stats2['win'], $stats2['lose'], $status2, $stats2['money']));

                    $this->sendSound($this->tile[0]->asVector3(), 'LevelUpSound');
                    $this->stopGame();
                    break;
                }
            }
        }
    
        return null;
    }

    public function stopGame() {
        $this->gameStarted = false;
        $this->currentPlayer = null;
        $this->gamePlayers = [];
        $this->refil();
    }

    public $refil = false;

    public function refil(bool $isStart = false) {
        if ($this->refil) {
            return;
        }
        $this->refil = true;
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this, $isStart) extends \pocketmine\scheduler\Task {
            public tictac $pl;
            public int $currentTileIndex = 0;
            public bool $isStart;

            public function __construct(tictac $pl, bool $isStart) {
                $this->pl = $pl;
                $this->isStart = $isStart;
            }

            public function onRun($currentTick) {
                if ($this->currentTileIndex >= count($this->pl->tile)) {
                    $this->pl->refil = false;

                    foreach ($this->pl->tile as $tile) {
                        $tile->setItemRotation(0);
                    }

                    if ($this->pl->gameStarted) {
                        $this->pl->sendMessage($this->pl->messages["start"]);
                    }

                    $this->pl->getServer()->getScheduler()->cancelTask($this->getTaskId());

                    return;
                }

                $tile = $this->pl->tile[$this->currentTileIndex];

                $this->pl->setItem($tile, $this->isStart ? $this->pl->items[tictac::N] : $this->pl->items[tictac::G]);

                foreach ($this->pl->tile as $tile) {
                    $tile->setItemRotation($this->currentTileIndex);
                }

                $this->currentTileIndex++;
            }
        }, 10);
    }

    private function areAllTilesFilled(): bool {
        foreach ($this->tile as $tile) {
            $item = $tile->getItem();
            if ($item->getId() === $this->items[self::N]->getId()) {
                return false;
            }
        }

        return true;
    }
    
    private function isTileSame(\pocketmine\tile\ItemFrame $tile1, \pocketmine\tile\ItemFrame $tile2, \pocketmine\tile\ItemFrame $tile3) {
        $item1 = $tile1->getItem();
        $item2 = $tile2->getItem();
        $item3 = $tile3->getItem();

        return $item1->getId() === $item2->getId() && $item2->getId() === $item3->getId();
    }
}