<?php

namespace ShopwareBot\Modules;

use ShopwareBot\Exceptions\DhlException;

/**
 * Class DhlModule
 * @package ShopwareBot\Modules
 */
class DhlModule extends BaseModule
{

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
            'detailsSalutation' => 'mr',
            'detailsFirstName' => '', // required
            'detailsLastName' => '', // required
            'detailsStreet' => '', // required
            'detailsStreetNumber' => '', // required
            'detailsStreetNumberBase' => '',
            'detailsStreetNumberExtension' => '',
            'detailsAdditionalAddressLine' => '',
            'detailsZipCode' => '', // required
            'detailsCity' => '', // required
            'detailsStateId' => 0,
            'detailsCountryId' => 2,
            'detailsCompany' => '',
            'detailsDepartment' => '',
            'detailsPhone' => '',
            'detailsEmail' => '',
            'packagingLength' => NULL,
            'packagingWidth' => NULL,
            'packagingHeight' => NULL,
            'packagingWeight' => '', // required
            'settingsProduct' => 1,
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
            'settingsMinimumAge' => NULL,
            'settingsPersonalHandover' => false,
            'settingsSaturdayDelivery' => false,
            'extraSettingsDayOfDelivery' => '',
            'extraSettingsPreferredDayOfDelivery' => '',
            'extraSettingsPreferredLocation' => '',
            'extraSettingsPreferredNeighbour' => '',
            'extraSettingsPreferredDeliveryTimeFrame' => '',
            'extraSettingsNoNeighbourDelivery' => false,
            'extraSettingsItemName' => '',
            'extraSettingsIgnorePrintOnlyIfCodeablePreset' => false,
            'extraSettingsInsuredValue' => '',
            'extraSettingsInsuredValueCurrency' => 'EUR',
        ];

    /**
     * @param $data
     * @return mixed
     * @throws DhlException
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

        if ($requiredMissingKeys) throw new DhlException('Label oluşturmak için zorunlu bilgileri girmalisiniz. Eksik bilgiler: ' . implode(', ', $requiredMissingKeys));

        if ($requiredEmptyKeys) throw new DhlException('Label oluşturmak için zorunlu bilgileri boş bırakmamalısınız. Boş bilgiler: ' . implode(', ', array_keys($requiredEmptyKeys)));


        $labelData = array_merge(self::CREATE_ORDER_DATA_TEMPLATE, $data);

//        _varexport(json_encode($labelData, JSON_PRETTY_PRINT));
        $this->client->getCurl()->setHeader('Content-Type', 'application/json');
        $res = $this->client->post('/ViisonDHLOrder/createLabel?_dc' . $this->getTimestamp(), json_encode($labelData));
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @return mixed
     * {"data":{"requiredFields":{"packageDimensionsRequired":false},"defaultPackageDimensions":{"defaultPackageLength":null,"defaultPackageWidth":null,"defaultPackageHeight":null},"isCashOnDelivery":false,"product":{"product":null},"splitAddress":{"streetName":"Fabrikstr.","houseNumber":"76","houseNumberParts":{"base":"76","extension":""},"additionalAddressLine":""},"shippingWeight":{"weight":1,"isDefault":true,"orderHasItemsWithoutWeight":true},"isCustomerMailTransferAllowed":false,"isCustomerPhoneTransferAllowed":false,"defaultDayOfDelivery":new Date(1546297200000)}}
     */
    public function getShippingOrderData($orderId)
    {
        $res = $this->client->post('/ViisonDHLOrder/getShippingOrderData', [
            'orderId' => $orderId
        ]);
        if ($res && !is_object($res))
            $res = json_decode($this->wrapClassNames($res));

        if (isset($res->data)) {

            if (!intval($res->data->splitAddress->houseNumber)) {
                // if housenumber can not be parsed, then get number in the street name
                $res->data->splitAddress->houseNumber = (int)filter_var($res->data->splitAddress->streetName, FILTER_SANITIZE_NUMBER_INT);;
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

        $res = $this->client->get('/ViisonDHLOrder/getAllLabels?' . $query);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    public function getAllConfigurations($page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDHLConfig/getAllConfigurations', [
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
        $res = $this->client->get('/ViisonDHLShipping/getProducts', [
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
     * {"success":true,"data":[{"isSalutationRequired":"1"}]}
     */
    public function getOrderConfigData($shopId, $page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDHLOrder/getOrderConfigData', [
            '_dc' => $this->getTimestamp(),
            'shopId' => $shopId,
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    public function calculateShippingWeight($orderId)
    {
        $res = $this->client->post('/ViisonDHLOrder/calculateShippingWeight', [
            'orderId' => $orderId
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    public function getMergedLabelsForOrder($orderId)
    {
        $res = $this->client->get('/ViisonDHLOrder/getMergedLabelsForOrder', [
            'orderId' => $orderId
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    public function getLabelPdf($code)
    {
        return $this->client->get('/ViisonDHLOrder/getDocument/label:' . $code);
    }

    /**
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getAllPreferredDeliveryTimeFrameData($page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDHLOrder/getAllPreferredDeliveryTimeFrameData', [
            '_dc' => $this->getTimestamp(),
            'page' => $page,
            'start' => $start,
            'limit' => $limit,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     * {"success":true,"data":null}
     */
    public function getMOBPackingStationData($orderId, $page = 1, $start = 0, $limit = 100)
    {
        $res = $this->client->get('/ViisonDHLOrder/getMOBPackingStationData', [
            '_dc' => $this->getTimestamp(),
            'orderId' => $orderId,
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
     * {"success":true,"data":{"postNumber":null}}
     */
    public function getShopwareDHLIntegrationData($orderId)
    {
        $res = $this->client->get('/ViisonDHLOrder/getShopwareDHLIntegrationData', [
            'orderId' => $orderId,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @return mixed
     * {"success":true,"personalHandover":false}
     */
    public function getPersonalHandover($orderId)
    {
        $res = $this->client->get('/ViisonDHLOrder/getPersonalHandover', [
            'orderId' => $orderId,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

    /**
     * @param $orderId
     * @return mixed
     * {"success":true,"minimumAge":null}
     */
    public function getDefaultMinimumAge($orderId)
    {
        $res = $this->client->get('/ViisonDHLOrder/getPersonalHandover', [
            'orderId' => $orderId,
        ]);
        if (isset($res->data)) return $res->data;
        return $res;
    }

}