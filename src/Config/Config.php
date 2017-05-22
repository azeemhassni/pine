<?php

namespace Pine\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Class Config.
 */
class Config implements \ArrayAccess
{
    /**
     * @var Yaml
     */
    protected $yaml;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Config constructor.
     *
     * @param Yaml $yaml
     */
    public function __construct(Yaml $yaml)
    {
        $this->yaml       = $yaml;
        $this->configPath = CONFIG_PATH;

        if ($this->isConfigured()) {
            $this->load();
        }
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return file_exists($this->configPath);
    }

    /**
     * load configurations from file.
     *
     * @return array|mixed
     */
    public function load()
    {
        $this->config = $this->yaml->parse(file_get_contents($this->configPath));

        return $this->config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Save configurations.
     *
     * @return bool
     */
    public function saveConfig()
    {
        $yaml = $this->yaml->dump($this->config);

        return (bool)file_put_contents($this->configPath, $yaml);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->config[ $key ];
    }

    /**
     * Whether a offset exists.
     *
     * @param mixed $key An offset to check for.
     * @return bool true on success or false on failure.
     *                   The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($key)
    {
        return isset($this->config[ $key ]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $key The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Offset to set.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $value                = $this->filter($key, $value);
        $this->config[ $key ] = $value;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function filter($key, $value)
    {
        $method = sprintf('set%sAttribute',
            implode('', array_map('ucwords', explode('_', $key)))
        );

        if (method_exists($this, $method)) {
            $value = $this->{$method}($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return string
     */
    public function setWpPasswordAttribute($value)
    {
        return md5($value);
    }

    /**
     * Unset config value.
     *
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        unset($this->config[ $key ]);
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->configPath;
    }
}
