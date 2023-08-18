<?php 

namespace Richen\Engine\Additions;

class Cases extends \Richen\Engine\Manager {

    const CASE_DONATE_DEFAULT = 0;
    const CASE_DONATE_FREE = 1;

    const PRICES = [
        self::CASE_DONATE_DEFAULT => 49,
        self::CASE_DONATE_FREE => 0,
    ];

    protected $groups = [];
    public function __construct() {
        foreach ($this->core()->conf('groups')->config()->getAll() as $groupName => $groupData) {
            $excluded = ['guest', 'richen', 'console', 'sponsor', 'kurator', 'moder', 'youtube', 'helper'];
            if (!in_array($groupName, $excluded)) {
                $this->groups[$groupName] = $groupData['price'];
            }
        }
    }

    public function getGroupsWithPrice() {
        return $this->groups;
    }

    public function getAvailableGroupsByUserId(int $user_id, int $type = 0) {
        switch ($type) {
            case self::CASE_DONATE_DEFAULT:
            case self::CASE_DONATE_FREE:
                $sql = 'SELECT SUM(price) AS total_price FROM cases WHERE user_id = :user_id AND type = :type';
                $stmt = $this->data()->conn()->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                $stmt->bindValue(':type', $type, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $sumOfCases = $row['total_price'] ?? 0;
                $groups = [];
                if ($sumOfCases < self::PRICES[$type] * 3) {
                    $groups = $this->getFirstGroupOfNum();
                } else {
                    foreach ($this->groups as $group => $groupPrice) {
                        if ($sumOfCases > $groupPrice) {
                            $groups[] = $group;
                        }
                    }
                }
                break;
        }
        return $groups;
    }

    public function getFirstGroupOfNum(int $num = 3): array {
        $groups = [];
        $i = 0;
        foreach ($this->groups as $group => $groupPrice) {
            $i++; if ($i > $num) break;
            $groups[] = $group;
        }
        return $groups;
    }

    public function getCases(int $user_id, int $type = 0): array {
        return $this->data()->get('cases', [['k' => 'user_id', 'v' => $user_id], ['k' => 'type', 'v' => $type], ['k' => 'status', 'v' => '1']]);
    }

    public function getAllCases(int $user_id, int $type = 0): array {
        return $this->data()->get('cases', [['k' => 'user_id', 'v' => $user_id], ['k' => 'type', 'v' => $type]]);
    }

    public function addCase(int $user_id, int $type, int $count) {
        for ($x = 0; $x < $count; $x++) {
            $this->data()->add('cases', ['user_id' => $user_id, 'type' => $type, 'price' => self::PRICES[$type]]);
        }
    }

    public function payCases(int $user_id, int $user2_id, int $count): bool {
        if ($this->getCases($user_id) < $count) return false;
        foreach ($this->getCases($user_id) as $case) {
            if ($count === 0) break;
            try {
                $this->data()->update('cases', ['status' => 0, 'result' => $user2_id], [['k' => 'id', 'v' => $case['id']]]);
            } catch (\Exception $e) {
                return false;
            }
            $count--;
        }
        $this->addCase($user2_id, 1, $count);
        return true;
    }

    public function openCase(int $user_id, int $case_id) {
        $group = $this->getRandomGroup($user_id);
        $this->data()->update('cases', ['result' => $group, 'modify' => time(), 'status' => 0], [['k' => 'id', 'v' => $case_id]]);
        return $group;
    }

    public function getRandomGroup(int $user_id) {
        $randomGroup = $this->getRandomAvailableGroup($this->getAvailableGroupsByUserId($user_id, self::CASE_DONATE_DEFAULT));
        return $randomGroup;
    }

    public function getRandomAvailableGroup(array $groups) {
        print_r($groups);
        $weightedGroups = [];
        $allgroups = $this->groups;
        $prices = array_values($allgroups);
        arsort($prices);
        foreach ($allgroups as $key => &$value) {            
            $value = array_shift($prices);
        }
        unset($value);

        foreach ($groups as $group) {
            $count = $allgroups[$group];
            for ($i = 0; $i < $count; $i++) {
                $weightedGroups[] = $group;
            }
        }
        $randomIndex = array_rand($weightedGroups);
        $randomGroup = $weightedGroups[$randomIndex];
        return $randomGroup;
    }
}