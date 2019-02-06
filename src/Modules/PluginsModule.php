<?php
namespace ShopwareBot\Modules;

/**
 * Class PluginsModule
 * @package ShopwareBot\Modules
 */
class PluginsModule extends BaseModule
{

    /**
     * @param $technicalName_or_Label
     * @return bool
     */
    public function isPluginActive($technicalName_or_Label)
    {
        $activePlugins = $this->getActivePlugins();
        foreach ($activePlugins as $activePlugin) {
            if (in_array($technicalName_or_Label, [$activePlugin->technicalName, $activePlugin->label])) return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getActivePlugins()
    {
        $plugins = $this->getLocalList();

        $activePlugins = array_filter($plugins, function ($a) {
            return boolval($a->active);
        });

        return $activePlugins;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @param array $group
     * @return mixed
     */
    public function getLocalList($page = 1, $start = 0, $limit = 100000, $sort = ['property' => 'groupingState', 'direction' => 'DESC'], $group = ['property' => 'groupingState', 'direction' => 'DESC'])
    {
        $res = $this->client->get('/PluginManager/localListing',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'group' => json_encode($group),
                'sort' => json_encode($sort)
            ]);


        return $res->data;
    }

}