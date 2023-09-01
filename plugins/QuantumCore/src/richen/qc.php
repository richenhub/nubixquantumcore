<?php 

declare(strict_types=1);

namespace richen;

class qc extends \pocketmine\plugin\PluginBase
{
    use config\properties;
    use helpers\helper;

    public function onEnable() {
        $this->log('Плагин запущен');
    }
}