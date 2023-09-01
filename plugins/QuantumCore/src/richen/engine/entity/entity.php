<?php declare(strict_types=1);

namespace richen\engine\entity;

class entity {
    private ?int $id = null;
    private ?int $created = null;
    private ?int $modified = null;
    public function getItemId(): ?int { return $this->id; }
    public function getCreated(): ?int { return $this->created; }
    public function getModified(): ?int { return $this->modified; }

    public function dataset(array $data) {
        if (isset($data['id']) && isset($data['created']) && isset($data['modified'])) {
            $this->id = $data['id'];
            $this->created = $data['created'];
            $this->modified = $data['modified'];
        }
    }
}