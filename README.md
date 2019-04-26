# Shopware Bot
Shopware Bot is library that make your backend turn into like a restapi. 
With this library, you can do some stuff that built-in shopware rest api does not support.

- Creating invoice and shipping labels
- Managing pickware plugin stock activities
- Searching orders, articles, users etc.

For now, these modules are available:
  - ArticleModule
  - CustomerModule
  - DhlModule
  - DpdModule
  - ImportExportModule
  - LogModule
  - OrderModule
  - PickwareModule
  - PluginsModule

### Installation

```sh
$ composer require erkineren/shopwarebot
```

### Quick Start

```php
use ShopwareBot\ShopwareBot;

require_once 'vendor/autoload.php';

$shop_url = "http://shopware.dev";
$username = 'user';
$password = 'password';

$bot = ShopwareBot::getInstance($shop_url);

try {
    // login to backend
    $bot->login($username, $password);
} catch (\ShopwareBot\Exceptions\CsrfException $e) {
    die($e->getMessage());
}

// Orders
$order = $bot->getOrderModule()->getOrderByNumber('12345');
$orders = $bot->getOrderModule()->getList($filter);

// Document
$bot->getOrderModule()->generateDocumentInvoice($orderId);
$bot->getOrderModule()->generateDocumentDeliveryNote($orderId);
$bot->getOrderModule()->generateDocumentCreditNote($orderId);
$bot->getOrderModule()->generateDocumentReversalInvoice($orderId);
$bot->getOrderModule()->generateDocument($orderId, $documentType); // custom document

// Customers
$bot->getCustomerModule()->getCustomerById($id);
$bot->getCustomerModule()->getCustomerByNumber($number);
$bot->getCustomerModule()->getCustomersByEmail($email);
$bot->getCustomerModule()->getCustomersByFirstname($firstname);
$bot->getCustomerModule()->getCustomersByFirstname($firstname);
$bot->getCustomerModule()->getCustomersByCustomerGroup($groupName);
$bot->getCustomerModule()->getCustomersByNumber($customernumbers);
$bot->getCustomerModule()->search($q);
$bot->getCustomerModule()->getBy($property, $value);
$bot->getCustomerModule()->getList($filter);
$bot->getCustomerModule()->getDetail($customerID);
$bot->getCustomerModule()->getUserAddressList($customerId);
$bot->getCustomerModule()->getUserOrders($customerID);
$bot->getCustomerModule()->getOrderChart($customerID);
$bot->getCustomerModule()->getUserPickwareArticles($customerID);
$bot->getCustomerModule()->delete($userId);

// Cache
$bot->getCacheModule()->clearShopCache();

// Logs
$bot->getLogModule()->createLog('Some logged data');


// ImportExport Plugin
$bot->getImportExportModule()->getProfiles();
$bot->getImportExportModule()->getProfiles();
$bot->getImportExportModule()->prepareExport($data);
$bot->getImportExportModule()->exportPartially(ImportExportModule::ARTICLES_COMPLETE, $limit); // for big data
$bot->getImportExportModule()->download($filename, 'files/saveto.csv');


// Pickware DPD,DHL adapters plugins
$bot->getDpdModule()->getShippingOrderData($orderId);
$bot->getDpdModule()->getAllLabels($orderId);
$bot->getDpdModule()->getTrackingUrls($trackingCodes);
$bot->getDpdModule()->getCountries();
$bot->getDpdModule()->getAllConfigurations();
$bot->getDpdModule()->getProducts();
$bot->getDpdModule()->getOrderConfigData($shopId);
$bot->getDpdModule()->calculateShippingWeight($orderId);
$bot->getDpdModule()->getMergedLabelsForOrder($orderId);
$bot->getDpdModule()->getLabelPdf($code);
$bot->getDpdModule()->getReturnLabelPdf($code);
// create shipping label
$bot->getDpdModule()->createLabel([
                'orderId' => $orderId,
                'detailsFirstName' => $address['firstname'],
                'detailsLastName' => $address['lastname'],
                'detailsStreet' => $address['street'],
                'detailsStreetNumber' => $address['no'],
                'detailsZipCode' => $address['postalcode'],
                'detailsCity' => $address['city'],
                'detailsStateId' => 0,
                'detailsCountryId' => $country['id'],
                'detailsEmail' => $address['email'],
                'packagingWeight' => 5
            ]);

// get shipping label that created from DPD plugin
ob_clean();
header("Content-type:application/pdf");
header("Content-Disposition:attachment;filename=" . $code . ".pdf");
echo $bot->getDpdModule()->getLabelPdf($code);
die;


// Pickware ERP Plugin
$bot->getPickwareModule()->saveIncomingStock($articleDetailId,$binLocationId,$warehouseId,$incomingStock);
$bot->getPickwareModule()->saveIncomingStock($articleDetailId,$binLocationId,$warehouseId,$incomingStock);
$bot->getPickwareModule()->getStockList();
$bot->getPickwareModule()->getStockListByOrderDetailId();
$bot->getPickwareModule()->getStockListByUserId();
$bot->getPickwareModule()->getStockListByComment();
$bot->getPickwareModule()->getStockListByType();


// Plugins
$bot->getPluginsModule()->isPluginActive($technicalName_or_Label);
$bot->getPluginsModule()->getActivePlugins();
$bot->getPluginsModule()->getLocalList();

// Articles
$bot->getArticleModule()->deleteProduct($Detail_id);

```


### Todos

 - Add more modules

License
----

GPL-3.0+
