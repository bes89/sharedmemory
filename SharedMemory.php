<?php

class SharedMemory
{
    protected $values = array();

    protected static $instance;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new SharedMemory();
        }

        return self::$instance;
    }

    protected function attach($key, $size)
    {
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = array(
                'shm' => shm_attach($key, $size),
                'mutex' => sem_get($key, 1)
            );
        }

        return $this->values[$key];
    }

    public function set($key, $value, $size = 10000)
    {
        $result = $this->attach($key, $size);

        sem_acquire($result['mutex']);
        shm_put_var($result['shm'], $key, $value);
        sem_release($result['mutex']);
    }

    public function get($key, $size = 10000)
    {
        $result = $this->attach($key, $size);

        sem_acquire($result['mutex']);
        $value = @shm_get_var($result['shm'], $key);
        sem_release($result['mutex']);

        return $value;
    }

}
