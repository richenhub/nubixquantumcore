<?php declare(strict_types=1);

namespace richen\engine\entity;

class player extends \pocketmine\Player
{
    private ?userentity $data = null;

    public function setData(userentity $data): void {
        $this->data = $data;
    }

    public function getData(): ?userentity {
        if(!$this->data) {
            $this->setData(new userentity($this->getLowerCaseName()));
        }

        return $this->data;
    }

    public function isRegistered(): bool {
        $data = $this->getData();

        return $data->getItemId() > 0 && $data->getPassword() !== 0;
    }

    public function isAuthed(): bool {
        if ($this->isRegistered()) {
            return false;
        }

        $data = $this->getData();

        return $data->getAddress() === $this->getAddress();
    }
}