<?php declare(strict_types=1);

namespace richen\managers\easyauth;

class easyauthquery extends \richen\managers\manager
{
    private array $users = [];
    private array $userIds = [];

    public function getUserByName(string $username)
    {
        $username = mb_strtolower($username);

        $id = $this->getIdByName($username);

        if ($id) {
            $user = array_filter($this->users, function($user) use ($id) { return $user['id'] === $id; });
    
            if (!empty($user)) {
                return reset($user);
            }
        } else {
            $data = $this->database()->get('users', [[ 'k' => 'username', 'v' => $username ]]);

            if (count($data)) {
                $user = $this->createObject(reset($data));
                
                $this->userIds[$username] = $user->id;

                return $this->users[$user->id] = $user;
            }
        }

        return null;
    }

    public function getUserById(int $id)
    {
        if (isset($this->users[$id])) {
            return $this->users[$id];
        }

        
        $data = $this->database()->get('users', [[ 'k' => 'id', 'v' => $id ]]);

        if (count($data)) {
            $user = $this->createObject(reset($data));
            
            $this->userIds[$user->username] = $id;

            return $this->users[$id] = $user;
        }

        return null;
    }

    public function getIdByName(string $name): ?int
    {
        return $this->userIds[mb_strtolower($name)] ?? null;
    }
}