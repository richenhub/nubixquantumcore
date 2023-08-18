<?php 

declare(strict_types=1);

namespace richen;

class qc extends \pocketmine\plugin\PluginBase
{
    public function onEnable() {
        $class = new helpers\classgenerator();
        $class->generateCommand('spawn');
    }
}