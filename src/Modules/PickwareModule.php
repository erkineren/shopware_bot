<?php

namespace ShopwareBot\Modules;
/**
 * Class PickwareModule
 * @package ShopwareBot\Modules
 */
class PickwareModule extends BaseModule
{

    const StockLedgerEntry_TYPE_PURCHASE = 'purchase';
    const StockLedgerEntry_TYPE_SALE = 'sale';
    const StockLedgerEntry_TYPE_RETURN = 'return';
    const StockLedgerEntry_TYPE_STOCKTAKE = 'stocktake';
    const StockLedgerEntry_TYPE_MANUAL = 'manual';
    const StockLedgerEntry_TYPE_INCOMING = 'incoming';
    const StockLedgerEntry_TYPE_OUTGOING = 'outgoing';
    const StockLedgerEntry_TYPE_RELOCATION = 'relocation';
    const StockLedgerEntry_TYPE_INITIALIZATION = 'initialization';

    /**
     * @param string $filterStr
     * @param bool $filterStockProblems
     * @param null $planningFrom
     * @param null $planningTo
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getArticles($filterStr = '', $filterStockProblems = false, $planningFrom = null, $planningTo = null, $page = 1, $start = 0, $limit = 500)
    {
        if ($planningFrom == null && $planningTo == null) {
            $planningTo = date('Y-m-d\T00:00:00');
            $planningFrom = date('Y-m-d\T00:00:00', strtotime("-30 days"));
        }

        $res = $this->client->get('/ViisonPickwareERPStockOverview/getArticles',
            [
                '_dc' => $this->getTimestamp(),
                'planningFrom' => $planningFrom,
                'planningTo' => $planningTo,
                'filterStr' => $filterStr,
                'filterStockProblems' => $filterStockProblems,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        if (!is_object($res))
            $res = json_decode($this->wrapClassNames($res), true);

        return $res;
    }

    /**
     * @param array $sort
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getWarehouseList($sort =
                                     [
                                         [
                                             'property' => 'warehouse.defaultWarehouse',
                                             'direction' => 'DESC',
                                         ],
                                         [
                                             'property' => 'warehouse.name',
                                             'direction' => 'ASC',
                                         ]
                                     ], $page = 1, $start = 0, $limit = 2000)
    {

        $res = $this->client->get('/ViisonPickwareERPWarehouseManagement/getWarehouseList',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'sort' => json_encode($sort)
            ]);

        if (!is_object($res))
            $res = json_decode($this->wrapClassNames($res), true);

        return $res;
    }

    /**
     * @param $articleDetailId
     * @param $binLocationId
     * @param $warehouseId
     * @param $incomingStock
     * @param $purchasePrice
     * @param $comment
     * @param $warehouseConfigId
     * @return mixed
     *
     * {
     * "success": true,
     * "data": {
     * "inStock": -1,
     * "stockMin": 0,
     * "pickwarePhysicalStockForSale": -1
     * }
     * }
     */
    public function saveIncomingStock($articleDetailId,
                                      $binLocationId,
                                      $warehouseId,
                                      $incomingStock,
                                      $comment = '',
                                      $purchasePrice = null,
                                      $warehouseConfigId = null)
    {
        return $this->client->post("/ViisonPickwareERPBinLocationEditor/saveIncomingStock", [
            'articleDetailId' => $articleDetailId,
            'binLocationId' => $binLocationId,
            'warehouseId' => $warehouseId,
            'incomingStock' => $incomingStock,
            'purchasePrice' => $purchasePrice,
            'comment' => $comment,
            'warehouseConfigId' => $warehouseConfigId,
        ]);
    }


    /**
     * @param $articleDetailId
     * @param $binLocationId
     * @param $warehouseId
     * @param $outgoingStock
     * @param $purchasePrice
     * @param $comment
     * @param $warehouseConfigId
     * @return mixed
     *
     * {
     * "success": true,
     * "data": {
     * "inStock": -1,
     * "stockMin": 0,
     * "pickwarePhysicalStockForSale": -1
     * }
     * }
     */
    public function saveOutgoingStock($articleDetailId,
                                      $binLocationId,
                                      $warehouseId,
                                      $outgoingStock,
                                      $comment = '',
                                      $purchasePrice = null,
                                      $warehouseConfigId = null)
    {

        $res = $this->client->post("/ViisonPickwareERPBinLocationEditor/saveOutgoingStock", [
            'articleDetailId' => $articleDetailId,
            'binLocationId' => $binLocationId,
            'warehouseId' => $warehouseId,
            'outgoingStock' => $outgoingStock,
            'purchasePrice' => $purchasePrice,
            'comment' => $comment,
            'warehouseConfigId' => $warehouseConfigId,
        ]);

        return $res;
    }


    //region getStockList ve alternatifleri

    /**
     * @param $filter
     * @param array $sort
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getStockList($filter, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        $res = $this->client->get('/ViisonPickwareERPArticleStock/getStockList',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'sort' => json_encode($sort),
                'filter' => json_encode($filter)
            ]);

        if (!is_object($res))
            $res = json_decode($this->wrapClassNames($res), true);

        return $res;
    }

    /**
     * @param $articleDetailId
     * @param $warehouseId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     * @throws \Exception
     */
    public function getStockListByArticleDetailId($articleDetailId, $warehouseId, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        return $this->getStockList([
            [
                'property' => 'stockLedgerEntry.articleDetailId',
                'value' => $articleDetailId,
                'operator' => NULL,
                'expression' => NULL,
            ],
            [
                'property' => 'stockLedgerEntry.warehouseId',
                'value' => $warehouseId,
                'operator' => NULL,
                'expression' => NULL,
            ]
        ], $page, $start, $limit, $sort);
    }

    /**
     * @param $orderDetailId
     * @param $warehouseId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     */
    public function getStockListByOrderDetailId($orderDetailId, $warehouseId, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        return $this->getStockList([
            [
                'property' => 'stockLedgerEntry.orderDetailId',
                'value' => $orderDetailId,
                'operator' => NULL,
                'expression' => NULL,
            ],
            [
                'property' => 'stockLedgerEntry.warehouseId',
                'value' => $warehouseId,
                'operator' => NULL,
                'expression' => NULL,
            ]
        ], $page, $start, $limit, $sort);
    }

    /**
     * @param $userId
     * @param $warehouseId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     */
    public function getStockListByUserId($userId, $warehouseId, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        return $this->getStockList([
            [
                'property' => 'stockLedgerEntry.userId',
                'value' => $userId,
                'operator' => NULL,
                'expression' => NULL,
            ],
            [
                'property' => 'stockLedgerEntry.warehouseId',
                'value' => $warehouseId,
                'operator' => NULL,
                'expression' => NULL,
            ]
        ], $page, $start, $limit, $sort);
    }

    /**
     * @param $comment
     * @param $warehouseId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     * @throws \Exception
     */
    public function getStockListByComment($comment, $warehouseId, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        return $this->getStockList([
            [
                'property' => 'stockLedgerEntry.comment',
                'value' => $comment,
                'operator' => NULL,
                'expression' => NULL,
            ],
            [
                'property' => 'stockLedgerEntry.warehouseId',
                'value' => $warehouseId,
                'operator' => NULL,
                'expression' => NULL,
            ]
        ], $page, $start, $limit, $sort);
    }

    /**
     * @param $type
     * @param $warehouseId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     * @throws \Exception
     */
    public function getStockListByType($type, $warehouseId, $page = 1, $start = 0, $limit = 500, $sort = [['property' => 'created', 'direction' => 'DESC']])
    {
        return $this->getStockList([
            [
                'property' => 'stockLedgerEntry.type',
                'value' => $type,
                'operator' => NULL,
                'expression' => NULL,
            ],
            [
                'property' => 'stockLedgerEntry.warehouseId',
                'value' => $warehouseId,
                'operator' => NULL,
                'expression' => NULL,
            ]
        ], $page, $start, $limit, $sort);
    }
    //endregion


}