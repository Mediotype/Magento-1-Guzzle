Guzzle 4.21 refactored completely for Magento, the most popular PHP HTTP client and webservice framework
================================================
@author Joel Hart @mediotype

http://www.mediotype.com

I refactored the entire code base, including comments so your PHPStorm will play nice :)

Guzzle is a PHP HTTP client that makes it easy to work with HTTP/1.1 and takes
the pain out of consuming web services.

```php
$client = Mage::getModel('guzzle/client');

#Guzzle's functions.php has been refactored into the modules Magento Helper

$helper = Mage::helper('guzzle');

$response = $client->get('http://guzzlephp.org');
$res = $client->get('https://api.github.com/user', array('auth' =>  array('user', 'pass')));
echo $res->getStatusCode();
// "200"
echo $res->getHeader('content-type');
// 'application/json; charset=utf8'
echo $res->getBody();
// {"type":"User"...'
var_export($res->json());
// Outputs the JSON decoded data
```

- Pluggable HTTP adapters that can send requests serially or in parallel
- Doesn't require cURL, but uses cURL by default
- Streams data for both uploads and downloads
- Provides event hooks & plugins for cookies, caching, logging, OAuth, mocks,
  etc.
- Keep-Alive & connection pooling
- SSL Verification
- Automatic decompression of response bodies
- Streaming multipart file uploads
- Connection timeouts

Get more information and answers with the
[Documentation](http://guzzlephp.org/),
[Forums](https://groups.google.com/forum/?hl=en#!forum/guzzle),
and IRC ([#guzzlephp](irc://irc.freenode.net/#guzzlephp) @ irc.freenode.net).

### Documentation

More information can be found in the online documentation at
http://guzzlephp.org/.