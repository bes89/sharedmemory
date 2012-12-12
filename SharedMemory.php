<?php

/**
 *
 * @author Fabian Frick <fabi.kcirf@gmail.com>
 * @author Besnik Brahimi <besnik.br@gmail.com>
 */
class SharedMemory implements ArrayAccess
{
    /**
     * @var array
     */
    protected $values = array();

    /**
     * @var SharedMemory
     */
    protected static $instance;

    /**
     *
     */
    protected function __construct()
    {
    }

    /**
     * @return SharedMemory
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new SharedMemory();
        }

        return self::$instance;
    }

    /**
     * @param $key
     * @param $size
     * @throws InvalidArgumentException
     * @return array
     */
    protected function attach($key, $size)
    {
        if (!array_key_exists($key, $this->values)) {
            if (!is_long($key)) {
                throw new InvalidArgumentException(sprintf('Expected type long for "key" but "%s" given.', gettype($key)));
            }
            $this->values[$key] = array(
                'shm' => shm_attach($key, $size),
                'mutex' => sem_get($key, 1)
            );
        }

        return $this->values[$key];
    }

    /**
     * @param $key
     * @param $value
     * @param int $size
     */
    public function set($key, $value, $size = 10000)
    {
        $result = $this->attach($key, $size);

        sem_acquire($result['mutex']);
        shm_put_var($result['shm'], $key, $value);
        sem_release($result['mutex']);
    }

    /**
     * @param $key
     * @param int $size
     * @return mixed
     */
    public function get($key, $size = 10000)
    {
        $result = $this->attach($key, $size);

        sem_acquire($result['mutex']);
        $value = @shm_get_var($result['shm'], $key);
        sem_release($result['mutex']);

        return $value;
    }

    /**
     * Checks whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->values);
    }

    /**
     * Retrieves a variable by offset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets value at the given offset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param int $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unsets a variable at the given offset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param int $offset The offset to unset.
     * @throws InvalidArgumentException
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (!array_key_exists($offset, $this->values)) {
            throw new InvalidArgumentException(sprintf('Undefined index "%s"'));
        }

        if (shm_remove_var($this->values[$offset]['shm'], $offset))
        {
            unset($this->values[$offset]);
        }
    }
}
