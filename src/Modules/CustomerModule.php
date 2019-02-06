<?php

namespace ShopwareBot\Modules;
/**
 * Class CustomerModule
 * @package ShopwareBot\Modules
 */
class CustomerModule extends BaseModule
{

    /**
     * @param $id
     * @return mixed
     */
    public function getCustomerById($id)
    {
        $res = $this->getBy('id', $id);

        if ($res && $res['success']) return $res['data'][0];

        return $res;
    }

    /**
     * @param $number
     * @return mixed
     */
    public function getCustomerByNumber($number)
    {
        $res = $this->getBy('number', $number);

        if ($res && $res['success']) return $res['data'][0];

        return $res;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getCustomerByEmail($email)
    {
        $res = $this->getBy('email', $email);

        if ($res && $res['success']) return $res['data'][0];

        return $res;
    }

    /**
     * @param $firstname
     * @return mixed
     */
    public function getCustomersByFirstname($firstname)
    {
        $res = $this->getBy('firstname', $firstname);

        return $res;
    }

    /**
     * @param $groupName
     * @return mixed
     */
    public function getCustomersByCustomerGroup($groupName)
    {
        $res = $this->getBy('customergroup.name', $groupName);

        return $res;
    }

    /**
     * @param $customernumbers
     * @return bool|mixed
     */
    public function getCustomersByNumber($customernumbers)
    {
        if (!is_array($customernumbers))
            $customernumbers = array_map('trim', explode(',', $customernumbers));

        if (!$customernumbers) return false;

        $res = $this->getBy('number', $customernumbers, null, 'IN');

        return $res;
    }

    /**
     * @param $q
     * @return mixed
     */
    public function search($q){
        return $this->getBy('search', $q);
    }

    /**
     * @param $property
     * @param $value
     * @param null $operator
     * @param null $expression
     * @return mixed
     */
    public function getBy($property, $value, $operator = null, $expression = null)
    {
        $res = $this->getList(
            [
                [
                    'property' => $property,
                    'value' => $value,
                    'operator' => $operator,
                    'expression' => $expression,
                ]
            ]
        );
        return $res;
    }

    /**
     * @param array $filter
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     * @throws \Exception
     */
    public function getList($filter = [], $page = 1, $start = 0, $limit = 100000, $sort = ['property' => 'id', 'direction' => 'DESC'])
    {
        $res = $this->client->get('/CustomerQuickView/list',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'filter' => json_encode($filter),
                'sort' => json_encode($sort)
            ]);

        return json_decode($this->wrapClassNames($res), true);
    }


    /**
     * @param $customerID
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getDetail($customerID, $page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/Customer/getDetail',
            [
                '_dc' => $this->getTimestamp(),
                'customerID' => $customerID,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return json_decode($this->wrapClassNames($res), true);
    }

    /**
     * @param $customerId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getUserAddressList($customerId, $page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/Address/list',
            [
                '_dc' => $this->getTimestamp(),
                'customerId' => $customerId,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }

    /**
     * @param $customerID
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getUserOrders($customerID, $page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/customer/getOrders',
            [
                '_dc' => $this->getTimestamp(),
                'customerID' => $customerID,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return json_decode($this->wrapClassNames($res), true);
    }

    /**
     * @param $customerID
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getOrderChart($customerID, $page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/customer/getOrderChart',
            [
                '_dc' => $this->getTimestamp(),
                'customerID' => $customerID,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }

    /**
     * @param $customerID
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param array $sort
     * @return mixed
     * @throws \Exception
     */
    public function getUserPickwareArticles($customerID, $page = 1, $start = 0, $limit = 100000, $sort = ['property' => 'order_.orderTime', 'direction' => 'DESC'])
    {
        $res = $this->client->get('/ViisonPickwareERPCustomerArticles/getArticleList',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'filter' => json_encode([
                    [
                        'property' => 'order_.customerId',
                        'value' => '27067',
                        'operator' => $customerID,
                        'expression' => null,
                    ]
                ]),
                'sort' => json_encode($sort)
            ]);

        return json_decode($this->wrapClassNames($res), true);
    }


    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function loadStores($page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/Customer/loadStores',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getSalutations($page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/Base/getSalutations',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getUserAttributeDataList($page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/Base/getSalutations',
            [
                '_dc' => $this->getTimestamp(),
                'table' => 's_user_attributes',
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getCountryStates($page = 1, $start = 0, $limit = 100000){
        $res = $this->client->get('/base/getCountryStates',
            [
                '_dc' => $this->getTimestamp(),
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
            ]);

        return $res;
    }





}