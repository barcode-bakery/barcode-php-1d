<p align="center"><a href="https://www.barcodebakery.com" target="_blank">
    <img src="https://www.barcodebakery.com/images/BCG-Logo-SQ-GitHub.svg">
</a></p>

[Barcode Bakery][1] is library written in PHP, [.NET Standard][32] and Node.JS which allows you to generate barcodes on the fly on your server for displaying or saving.

The library has minimal dependencies in each language in order to be supported on a wide variety of web servers.

The library is available for free for non-commercial use; however you must [purchase a license][2] if you plan to use it in a commercial environment.

Installation
------------
There are two ways to install our library:

* With composer, run the following command:
```sh
composer require barcode-bakery/barcode-1d
```
* Or, download the library on our [website][3], and follow our [developer's guide][4].

Requirements
------------
* PHP 7.4+ or PHP8
* GD2

Example usages
--------------
For a full example of how to use each symbology type, visit our [API page][5].

### Displaying a Code 128 on the screen
```php
<?php
// Path to the generated autoload file.
require __DIR__ . '/../vendor/autoload.php';

use BarcodeBakery\Common\BCGFontFile;
use BarcodeBakery\Common\BCGColor;
use BarcodeBakery\Common\BCGDrawing;
use BarcodeBakery\Barcode\BCGcode128;

$font = new BCGFontFile(__DIR__ . '/font/Arial.ttf', 18);
$colorBlack = new BCGColor(0, 0, 0);
$colorWhite = new BCGColor(255, 255, 255);

// Barcode Part
$code = new BCGcode128();
$code->setScale(2);
$code->setThickness(30);
$code->setForegroundColor($colorBlack);
$code->setBackgroundColor($colorWhite);
$code->setFont($font);
$code->setStart(null);
$code->setTilde(true);
$code->parse('a123');

// Drawing Part
$drawing = new BCGDrawing($code, $colorWhite);

header('Content-Type: image/png');
$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
```

### Saving the image to a file
Replace the last lines of the previous code with the following:
```php
// Drawing Part
$drawing = new BCGDrawing($code, $colorWhite);
$drawing->finish(BCGDrawing::IMG_FORMAT_PNG, 'path/to/file.png');
```

This will generate the following:
<br />
<img src="https://www.barcodebakery.com/images/code-128-github.png">

Supported types
---------------
* [Codabar][6]
* [Code 11][7]
* [Code 128][8]
* [Code 39][9]
* [Code 39 Extended][10]
* [Code 93][11]
* [EAN-13][12]
* [EAN-8][13]
* [GS1-128 (EAN-128)][14]
* [Intelligent Mail][15]
* [Interleaved 2 of 5][16]
* [ISBN-10 / ISBN-13][17]
* [MSI Plessey][18]
* [Other (Custom)][19]
* [Postnet][20]
* [Standard 2 of 5][21]
* [UPC Extension 2][22]
* [UPC Extension 5][23]
* [UPC-A][24]
* [UPC-E][25]

Other libraries available for purchase
--------------------------------------
* [Aztec][26]
* [Databar Expanded][27]
* [DataMatrix][28]
* [MaxiCode][29]
* [PDF417][30]
* [QRCode][31]


[1]: https://www.barcodebakery.com
[2]: https://www.barcodebakery.com/en/purchase
[3]: https://www.barcodebakery.com/en/download
[4]: https://www.barcodebakery.com/en/docs/php/guide
[5]: https://www.barcodebakery.com/en/docs/php/barcode/1d
[6]: https://www.barcodebakery.com/en/docs/php/barcode/codabar/api
[7]: https://www.barcodebakery.com/en/docs/php/barcode/code11/api
[8]: https://www.barcodebakery.com/en/docs/php/barcode/code128/api
[9]: https://www.barcodebakery.com/en/docs/php/barcode/code39/api
[10]: https://www.barcodebakery.com/en/docs/php/barcode/code39extended/api
[11]: https://www.barcodebakery.com/en/docs/php/barcode/code93/api
[12]: https://www.barcodebakery.com/en/docs/php/barcode/ean13/api
[13]: https://www.barcodebakery.com/en/docs/php/barcode/ean8/api
[14]: https://www.barcodebakery.com/en/docs/php/barcode/gs1128/api
[15]: https://www.barcodebakery.com/en/docs/php/barcode/intelligentmail/api
[16]: https://www.barcodebakery.com/en/docs/php/barcode/i25/api
[17]: https://www.barcodebakery.com/en/docs/php/barcode/isbn/api
[18]: https://www.barcodebakery.com/en/docs/php/barcode/msi/api
[19]: https://www.barcodebakery.com/en/docs/php/barcode/othercode/api
[20]: https://www.barcodebakery.com/en/docs/php/barcode/postnet/api
[21]: https://www.barcodebakery.com/en/docs/php/barcode/s25/api
[22]: https://www.barcodebakery.com/en/docs/php/barcode/upcext2/api
[23]: https://www.barcodebakery.com/en/docs/php/barcode/upcext5/api
[24]: https://www.barcodebakery.com/en/docs/php/barcode/upca/api
[25]: https://www.barcodebakery.com/en/docs/php/barcode/upce/api
[26]: https://www.barcodebakery.com/en/docs/php/barcode/aztec/api
[27]: https://www.barcodebakery.com/en/docs/php/barcode/databarexpanded/api
[28]: https://www.barcodebakery.com/en/docs/php/barcode/datamatrix/api
[29]: https://www.barcodebakery.com/en/docs/php/barcode/maxicode/api
[30]: https://www.barcodebakery.com/en/docs/php/barcode/pdf417/api
[31]: https://www.barcodebakery.com/en/docs/php/barcode/qrcode/api
[32]: https://github.com/barcode-bakery/barcode-dotnet-1d/
