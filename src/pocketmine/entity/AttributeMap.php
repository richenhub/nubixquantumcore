<?php

namespace pocketmine\entity;

class AttributeMap implements \ArrayAccess {
	/** @var Attribute[] */
	private $attributes = [];

	/**
	 * @param Attribute $attribute
	 */
	public function addAttribute(Attribute $attribute){
		$this->attributes[$attribute->getId()] = $attribute;
	}

	/**
	 * @param int $id
	 *
	 * @return Attribute|null
	 */
	public function getAttribute(int $id){
		return $this->attributes[$id] ?? null;
	}

	/**
	 * @return Attribute[]
	 */
	public function getAll() : array{
		return $this->attributes;
	}

	/**
	 * @return Attribute[]
	 */
	public function needSend() : array{
		return array_filter($this->attributes, function(Attribute $attribute){
			return $attribute->isSyncable() and $attribute->isDesynchronized();
		});
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset){
		return isset($this->attributes[$offset]);
	}

	/**
	 * @param mixed $offset
	 *
	 * @return float
	 */
	public function offsetGet($offset){
		return $this->attributes[$offset]->getValue();
	}

	/**
	 * @param int|null $offset
	 * @param float    $value
	 */
	public function offsetSet($offset, $value){
		if($offset === null){
			throw new \InvalidArgumentException("Array push syntax is not supported");
		}
		$this->attributes[$offset]->setValue($value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset){
		throw new \RuntimeException("Could not unset an attribute from an attribute map");
	}
}
