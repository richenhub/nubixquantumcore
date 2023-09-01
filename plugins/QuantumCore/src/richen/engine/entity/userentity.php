<?php 

declare(strict_types=1);

namespace richen\engine\entity;

class userentity extends entity {
    public \richen\engine\database\query $query;

    public function __construct(string $username) {
        $this->query = \richen\qc::getInstance()->query;

        $data = $this->query->get('users', [['k' => 'username', 'v' => mb_strtolower($username)]]);

        if (count($data)) {
            $data = $data[0];
            $this->username  = $data['username'];
            $this->password  = $data['password'];
            $this->address   = $data['address'];
            $this->lastlogin = $data['lastlogin'];
            $this->group     = $data['group'];
        }
    }

    private ?string $username = null;
    private ?string $password = null;
    private ?string $address = null;
    private ?string $lastlogin = null;
    public ?string $group = 'guest';
    public ?int $money = 0;

    public function getUsername(): ?string {
        return $this->username;
    }
    
    public function getPassword(): ?string {
        return $this->password;
    }
    
    public function getAddress(): ?string {
        return $this->address;
    }
    
    public function getGroup(): ?string {
        return $this->group;
    }

    public function getMoney(): ?int {
        return $this->money;
    }

    public function getLastLogin(): ?string {
        return $this->lastlogin;
    }
}