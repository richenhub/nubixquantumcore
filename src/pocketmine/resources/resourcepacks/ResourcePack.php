<?php

namespace pocketmine\resources\resourcepacks;


interface ResourcePack{

	/**
	 * Returns the path to the resource pack. This might be a file or a directory, depending on the type of pack.
	 */
	public function getPath() : string;

	/**
	 * @return string
	 */
	public function getPackName() : string;

	/**
	 * @return string
	 */
	public function getPackId() : string;

	/**
	 * @return int
	 */
	public function getPackSize() : int;

	/**
	 * @return string
	 */
	public function getPackVersion() : string;

	/**
	 * @return string
	 */
	public function getSha256() : string;

	/**
	 * Returns a chunk of the resource pack zip as a byte-array for sending to clients.
	 *
	 * Note that resource packs must **always** be in zip archive format for sending.
	 * A folder resource loader may need to perform on-the-fly compression for this purpose.
	 *
	 * @param int $start Offset to start reading the chunk from
	 * @param int $length Maximum length of data to return.
	 *
	 * @return string byte-array
	 * @throws \InvalidArgumentException if the chunk does not exist
	 */
	public function getPackChunk(int $start, int $length) : string;
}