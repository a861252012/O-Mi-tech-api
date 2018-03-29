<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/26
 * Time: 22:08
 */

namespace App\Services\Site;


use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Redis;

class Config implements Arrayable, ArrayAccess, Jsonable
{
    const KEY_SITE_CONFIG = 'hsite_config:';

    protected $siteID;
    protected $attributes = [];
    protected $redis;

    public function __construct($siteID, $attributes = [])
    {
        $this->siteID = $siteID;
        $this->redis = resolve('redis');
        $this->attributes = $attributes;
    }

    /**
     * @param      $name
     * @param bool $noCache 跳过本地缓存，读取redis
     * @return mixed|null
     */
    public function get($name, $noCache = false)
    {
        if (!$noCache && isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        $val = $this->redis->hget(static::KEY_SITE_CONFIG . $this->siteID, $name);
        if (is_null($val) || $val === false) {
            unset($this->attributes[$name]);
            return null;
        }
        return $this->attributes[$name] = $val;
    }

    public function isValid()
    {
        return Redis::exists(static::KEY_SITE_CONFIG . $this->siteID);
    }

    public function flush()
    {
        return $this->flushByID($this->siteID);
    }

    public static function flushByID($id)
    {
        return Redis::del(static::KEY_SITE_CONFIG . $id);
    }


    public function all()
    {
        return $this->attributes = Redis::hGetAll(static::KEY_SITE_CONFIG . $this->siteID);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     * @return boolean true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public static function hMset($id,$configArray)
    {
        return Redis::hMSet(static::KEY_SITE_CONFIG . $id, $configArray);
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->attributes, $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}