<?php declare(strict_types=1);

namespace richen\engine;

class objectdata
{
    private $data = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value)
        {
            $this->data[mb_strtolower($key)] = $value;
        }
    }

    public function __get($name)
    {
        if (array_key_exists(mb_strtolower($name), $this->data)) {
            return $this->data[mb_strtolower($name)];
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $this->data[mb_strtolower($name)] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[mb_strtolower($name)]);
    }

    public function __unset($name)
    {
        unset($this->data[mb_strtolower($name)]);
    }
}