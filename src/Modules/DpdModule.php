<?php

namespace ShopwareBot\Modules;
use ShopwareBot\Exceptions\DpdException;

/**
 * Class DpdModule
 * @package ShopwareBot\Modules
 */
class DpdModule extends BaseModule
{

    /**
     *
     */
    const CREATE_ORDER_DATA_TEMPLATE =
        [
            'id' => 0,
            'orderId' => 0, // required
            'trackingCode' => '',
            'url' => '',
            'created' => '',
            'exportDocumentUrl' => '',
            'customerAddress' => '',
            'returnShipment' => false,
            'shippingLabelId' => 0,
            'documents' => NULL,
            'entityName' => '',
            'useDetails' => true,
            'detailsSalutation' => '',
            'detailsFirstName' => '', // required
            'detailsLastName' => '', // required
            'detailsStreet' => '', // required
            'detailsStreetNumber' => '', // required
            'detailsStreetNumberBase' => '',
            'detailsStreetNumberExtension' => '',
            'detailsAdditionalAddressLine' => '',
            'detailsZipCode' => '', // required
            'detailsCity' => '', // required
            'detailsStateId' => 0, // required
            'detailsCountryId' => 2, // required
            'detailsCompany' => '',
            'detailsDepartment' => '',
            'detailsPhone' => '',
            'detailsEmail' => '', // required
            'packagingLength' => NULL,
            'packagingWidth' => NULL,
            'packagingHeight' => NULL,
            'packagingWeight' => '', // required
            'settingsProduct' => 1, // required
            'settingsCreateExportDocument' => false,
            'settingsSaveInOrder' => false,
            'settingsCashOnDelivery' => false,
            'extraSettingsAmount' => NULL,
            'extraSettingsCurrency' => NULL,
            'extraSettingsInsuranceAmount' => NULL,
            'extraSettingsInsuranceCurrency' => NULL,
            'creationSuccess' => true,
            'message' => '',
            'errorCode' => 0,
        ];


    /**
     * @param $data
     * @return mixed
     *
     * return data example
     * array (
     * 'id' => 183,
     * 'orderId' => 240,
     * 'trackingCode' => '3243546758687',
     * 'url' => 'https://{base_url}.com/backend/ViisonDPDOrder/getDocument/label:3243546758687',
     * 'created' => '2018-11-19T12:16:08Z',
     * 'exportDocumentUrl' => '',
     * 'returnShipment' => 0,
     * 'creationSuccess' => 1,
     * );
     *
     *
     *
     *
     * @throws DpdException
     */
    public function createLabel($data)
    {

        $requiredKeys = [
            'orderId',
            'detailsFirstName',
            'detailsLastName',
            'detailsStreet',
            'detailsZipCode',
            'detailsCity',
            'detailsStateId',
            'detailsCountryId',
            'detailsEmail',
            'packagingWeight'
        ];

        $requiredMissingKeys = array_diff_key(array_flip($requiredKeys), $data);

        $requiredEmptyKeys = array_filter($data, function ($a) {
            return $a === null || $a === '' || trim($a) === '';
        });

        if ($requiredMissingKeys) throw new DpdException('Label oluşturmak için zorunlu bilgileri girmalisiniz. Eksik bilgiler: ' . implode(', ', $requiredMissingKeys));

        if ($requiredEmptyKeys) throw new DpdException('Label oluşturmak için zorunlu bilgileri boş bırakmamalısınız. Boş bilgiler: ' . implode(', ', array_keys($requiredEmptyKeys)));


        $labelData = array_merge(self::CREATE_ORDER_DATA_TEMPLATE, $data);

//        _varexport(json_encode($labelData, JSON_PRETTY_PRINT));
        $this->client->getCurl()->setHeader('Content-Type', 'application/json');
        $res = $this->client->post('/ViisonDPDOrder/createLabel?_dc' . $this->getTimestamp(), json_encode($labelData));
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getShippingOrderData($orderId)
    {
        $res = $this->client->post('/ViisonDPDOrder/getShippingOrderData', [
            'orderId' => $orderId
        ]);
        if (isset($res->data)) {

            if (!intval($res->data->splitAddress->houseNumber)) {
                // if housenumber can not be parsed, then get number in the street name
                $res->data->splitAddress->houseNumber = (int)filter_var($res->data->splitAddress->streetName, FILTER_SANITIZE_NUMBER_INT);
                if (!$res->data->splitAddress->houseNumber) {
                    preg_match_all('/str.(\d+)/i', $res->data->splitAddress->streetName, $matches);
                    $res->data->splitAddress->houseNumber = $matches[1];
                }
            }

            return $res->data;
        }
        return $res;
    }

    /**
     * @param $orderId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @param string $sortProperty
     * @param string $sortDirection
     * @return mixed
     */
    public function getAllLabels($orderId, $page = 1, $start = 0, $limit = 100, $sortProperty = 'created', $sortDirection = 'DESC')
    {

        $data = [
            '_dc' => $this->getTimestamp(),
            'orderId' => $orderId . '',
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
            'sort' => json_encode([
                [
                    'property' => $sortProperty,
                    'direction' => $sortDirection
                ]
            ])
        ];

        $query = http_build_query($data);

        $res = $this->client->get('/ViisonDPDOrder/getAllLabels?' . $query);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $trackingCodes
     * @return mixed
     */
    public function getTrackingUrls($trackingCodes)
    {
        $res = $this->client->post('/ViisonShippingCommonOrder/getTrackingUrls', [
            'trackingCodes' => $trackingCodes,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getCountries($page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonShippingCommonFreeFormLabels/getCountries', [
            '_dc' => $this->getTimestamp(),
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getAllConfigurations($page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDPDConfig/getAllConfigurations', [
            '_dc' => $this->getTimestamp(),
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param string $shopId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getProducts($shopId = '', $page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDPDShipping/getProducts', [
            '_dc' => $this->getTimestamp(),
            'shopId' => $shopId,
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $shopId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getOrderConfigData($shopId, $page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDPDOrder/getOrderConfigData', [
            '_dc' => $this->getTimestamp(),
            'shopId' => $shopId,
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }


    /**
     * @param $orderId
     * @return mixed
     */
    public function calculateShippingWeight($orderId)
    {
        $res = $this->client->post('/ViisonDPDOrder/calculateShippingWeight', [
            'orderId' => $orderId
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getMergedLabelsForOrder($orderId)
    {
        $res = $this->client->get('/ViisonDPDOrder/getMergedLabelsForOrder', [
            'orderId' => $orderId
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getLabelPdf($code)
    {
        return $this->client->get('/ViisonDPDOrder/getDocument/label:' . $code);
    }

}
