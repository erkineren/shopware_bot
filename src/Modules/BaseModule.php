<?php

namespace ShopwareBot\Modules;
use ShopwareBot\ShopwareBot;
use ShopwareBot\Utils\JsonUtils;

/**
 * Class BaseModule
 *
 * @property JsonUtils JsonUtils
 */
class BaseModule
{
    /**
     * @var ShopwareBot
     */
    protected $client;

    /**
     * BaseModule constructor.
     * @param string $client
     */
    public function __construct($client = '')
    {
        $this->client = $client;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __get($name)
    {
        if (!preg_match('/^([a-z]+Utils)$/i', $name, $matches)) {
            return false;
        }

        $className = $matches[1];

        if (!class_exists($className)) {
            return false;
        }

        return new $className();
    }

    /**
     * @return int
     */
    protected function getTimestamp()
    {
        return intval(microtime(true) * 1000);
    }

    /**
     * @param $data
     * @return bool|string
     */
    protected function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }

    /**
     * @param $jsonString
     * @param bool $convertDate
     * @return null|string|string[]
     * @throws \Exception
     */
    public function wrapClassNames($jsonString, $convertDate = true)
    {
        $res = preg_replace_callback('/new \w+\([-\w]+\)/i', function ($a) {

            if (strpos($a[0], 'new Date(') === 0) {
                $millisecs = filter_var($a[0], FILTER_SANITIZE_NUMBER_INT);
                $date = date('Y-m-d H:i:s', $millisecs / 1000);
                return '"' . $date . '"';
            }

            return '"' . $a[0] . '"';
        }, $jsonString);

        if (preg_last_error() > 0)
            throw new \Exception('wrapClassNames preg_replace_callback error: ' . preg_error_msg(preg_last_error()));

        return $res;
    }

}