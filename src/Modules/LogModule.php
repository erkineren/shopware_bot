<?php

namespace ShopwareBot\Modules;
/**
 * Class LogModule
 * @package ShopwareBot\Modules
 */
class LogModule extends BaseModule
{
    /**
     * @param $text
     * @param string $module
     * @param string $user
     * @param string $type
     * @return mixed
     */
    public function createLog($text, $module = '', $user = '', $type = 'backend')
    {
        return $this->client->post("/Log/createLog", [
            'type' => $type,
            'key' => $module,
            'text' => $text,
            'user' => $user,
            'value4' => ''
        ]);
    }
}