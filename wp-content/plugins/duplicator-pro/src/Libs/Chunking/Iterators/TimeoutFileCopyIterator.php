<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Libs\Chunking\Iterators;

use Duplicator\Models\Storages\AbstractStorageAdapter;
use Exception;

class TimeoutFileCopyIterator implements GenericSeekableIteratorInterface
{
    /** @var string[] */
    protected $from = [];
    /** @var string[] */
    protected $to = [];
    /** @var int<0, max> */
    protected $bytesParsed = 0;
    /** @var int<-1, max> */
    protected $totalSize = -1;
    /** @var int[] */
    protected $position = [
        0,
        0,
    ];
    /** @var int<-1, max> */
    protected $currentSize = -1;
    /** @var string */
    protected $currentFrom = '';
    /** @var string */
    protected $currentTo = '';
    /** @var int<0, max> */
    protected $iterations = 0;
    /** @var AbstractStorageAdapter|null */
    protected $adapter = null;
    /** @var callable|null */
    protected $onNextFileCallback = null;

    /**
     * The iterator does not need offset information. The iterator skips files and chunks based on
     * file existence and filesize.
     *
     * @param array<string, string>       $replacements       array of paths to copy in the format [$from => $to]
     * @param AbstractStorageAdapter|null $adapter            Storage adapter
     * @param callable|null               $onNextFileCallback Is called when the current handled file changes
     */
    public function __construct($replacements, $adapter, $onNextFileCallback = null)
    {
        if (!is_array($replacements)) {
            throw new Exception('Remplacments must be an array');
        }

        if (!($adapter instanceof AbstractStorageAdapter)) {
            throw new Exception('Adapter must be an instance of AbstractStorageAdapter');
        }

        $this->adapter            = $adapter;
        $this->onNextFileCallback = is_callable($onNextFileCallback) ? $onNextFileCallback : null;
        $this->from               = array_keys($replacements);
        $this->to                 = array_values($replacements);
        $this->rewind();
    }

    /**
     * Set total size of replacemente list
     *
     * @return int total size
     */
    public function setTotalSize()
    {
        $this->totalSize = 0;
        foreach ($this->from as $file) {
            if (!$this->adapter->isFile($file)) {
                continue;
            }

            $this->totalSize += $this->adapter->fileSize($file);
        }

        return $this->totalSize;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = [
            0,
            0,
        ];
        $this->setCurrentItem(0);
        $this->bytesParsed = 0;
        $this->iterations  = 0;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool Returns true on success or false on failure.
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return ($this->position[0] < count($this->from));
    }

    /**
     * @param int[] $position position to seek to
     *
     * @return bool
     */
    public function gSeek($position)
    {
        if (!is_array($position) || count($position) !== 2) {
            return false;
        }
        $this->setCurrentItem($position[0], $position[1]);

        $this->bytesParsed = 0;
        for ($i = 0; $i < $this->position[0]; $i++) {
            $file = $this->from[$i];
            if (!$this->adapter->isFile($file)) {
                continue;
            }
            $this->bytesParsed += $this->adapter->fileSize($file);
        }
        $this->bytesParsed += $this->position[1];

        return true;
    }

    /**
     *
     * @return array{from: string, to: string, offset: int<0, max>}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return [
            'from'   => $this->currentFrom,
            'to'     => $this->currentTo,
            'offset' => $this->position[1],
        ];
    }

    /**
     * Update current file offset, bytes updated from timeout copy function
     *
     * @param int $bytesToAdd Bytes to add to the current offset
     *
     * @return int New offset
     */
    public function updateCurrentFileOffset($bytesToAdd)
    {
        $this->iterations++;
        $this->position[1] += $bytesToAdd;
        $this->bytesParsed += $bytesToAdd;
        return $this->position[1];
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->iterations++;

        // The next function don't update the current file offset,
        // it only updates the current file because is manually set from updateCurrentFileOffset function.

        if (($this->position[1] + 1) >= $this->currentSize) {
            $this->setCurrentItem(($this->position[0] + 1), 0);
            if (is_callable($this->onNextFileCallback)) {
                call_user_func($this->onNextFileCallback);
            }
        }
    }

    /**
     * Set current item
     *
     * @param int<0, max> $index  item index
     * @param int<0, max> $offset item offset
     *
     * @return void
     */
    protected function setCurrentItem($index, $offset = 0)
    {
        $this->position[0] = $index;
        $this->currentFrom = (isset($this->from[$index]) ? $this->from[$index] : '');
        $this->currentTo   = (isset($this->to[$index]) ? $this->to[$index] : '');
        $this->position[1] = $offset;

        if ($this->adapter->isFile($this->currentFrom)) {
            $this->currentSize = $this->adapter->fileSize($this->currentFrom);
        } else {
            $this->currentSize = -1;
        }
    }

    /**
     * Return current position
     *
     * @return int[]
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Free resources in current iteration
     *
     * @return void
     */
    public function stopIteration()
    {
    }

    /**
     * Return the key of the current element
     *
     * @return string the key
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return implode('_', $this->position);
    }

    /**
     * Return iterations count
     *
     * @return int
     */
    public function itCount()
    {
        return $this->iterations;
    }

    /**
     * Return progress percentage
     *
     * @return float progress percentage or -1 undefined
     */
    public function getProgressPerc()
    {
        if ($this->totalSize < 0) {
            $result = -1;
        } elseif ($this->totalSize == 0 || $this->bytesParsed >= $this->totalSize) {
            $result = 100;
        } else {
            $result = ($this->bytesParsed / $this->totalSize) * 100;
        }

        return (float) $result;
    }
}
