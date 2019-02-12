# ShopwareBot
Shopware Backend Bot is library that supply to use programatically backend like creating invoice and shipping labels, managing pickware plugin stock activities, searching orders, articles, users etc.

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

$shop_url = "https://yourShopwareSiteDomain.tld";
$username = 'backendUsername';
$password = 'backendPassword';

$bot = ShopwareBot::getInstance($shop_url);

try {
    $bot->login($username, $password);
} catch (\ShopwareBot\Exceptions\CsrfException $e) {
    die($e->getMessage());
}

$order = $bot->getOrderModule()->getOrderByNumber('12345');

print_r($order);

```


### Todos

 - Add more modules

License
----

GPL-3.0+
