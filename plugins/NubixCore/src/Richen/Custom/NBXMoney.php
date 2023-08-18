<?php 

namespace Richen\Custom;

class NBXMoney extends \Richen\Engine\Manager {
    protected int $id, $owner_id, $money, $nubix, $debet, $bitcs;
    public function __construct(int $id, int $owner_id, int $money, int $nubix, int $bitcs, int $debet) {
        $this->id = $id;
        $this->owner_id = $owner_id;
        $this->money = $money;
        $this->nubix = $nubix;
        $this->bitcs = $bitcs;
        $this->debet = $debet;
    }

    public function exists() { return $this->owner_id !== -1; }

    public function getMoney() { return $this->money; }
    public function addMoney(int $value) { $this->setMoney($this->money + $value); }
    public function delMoney(int $value) { $this->setMoney($this->money - $value); }
    public function setMoney(int $value) { $this->money = min(max($value, 0), 500000); $this->save(); }

    public function getNubix() { return $this->nubix; }
    public function addNubix(int $value) { $this->setNubix($this->nubix + $value); }
    public function delNubix(int $value) { $this->setNubix($this->nubix - $value); }
    public function setNubix(int $value) { $this->nubix = min(max($value, 0), 1000000); $this->save(); }

    public function getBitcs() { return $this->bitcs; }
    public function addBitcs(int $value) { $this->setBitcs($this->bitcs + $value); }
    public function delBitcs(int $value) { $this->setBitcs($this->bitcs - $value); }
    public function setBitcs(int $value) { $this->bitcs = min(max($value, 0), 500); $this->save(); }

    public function getDebet() { return $this->debet; }
    public function addDebet(int $value) { $this->setDebet($this->debet + $value); }
    public function delDebet(int $value) { $this->setDebet($this->debet - $value); }
    public function setDebet(int $value) { $this->debet = min(max($value, 0), 10000000); $this->save(); }

    public function save() {
        if ($this->owner_id !== -1) {
            $this->data()->add('cash', ['owner_id' => $this->owner_id, 'money' => $this->money, 'nubix' => $this->nubix, 'bitcs' => $this->bitcs, 'debet' => $this->debet]);
            $this->core()->cash()->cash[$this->owner_id] = $this;
        }
    }
}