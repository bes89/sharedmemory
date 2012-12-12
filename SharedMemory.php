<?php

/**
 *
 * @author Fabian Frick <fabi.kcirf@gmail.com>
 * @author Besnik Brahimi <besnik.br@gmail.com>
 */
class SharedMemory
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
                throw new InvalidArgumentException('Expected type long for "key" but "' . gettype($key) . '" given.');
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

}
