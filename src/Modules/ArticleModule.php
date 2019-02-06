<?php

namespace ShopwareBot\Modules;

/**
 * Class ArticleModule
 * @package ShopwareBot\Modules
 */
class ArticleModule extends BaseModule
{
    /**
     * @param $Detail_id
     * @return mixed
     */
    public function deleteProduct($Detail_id)
    {
        return $this->client->get("/ArticleList/deleteProduct", [
            '_dc' => $this->getTimestamp(),
            'Detail_id' => $Detail_id,
        ]);
    }
}