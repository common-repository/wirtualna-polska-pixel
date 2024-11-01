=== Wirtualna Polska Pixel ===

Contributors: wppixel
Tags: track, wppixel, pixel
Requires at least: 4.7
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.2.2

License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A tool for measuring actions taken by users visiting the website and increasing the effectiveness of advertising on the WP Advertising Network.

== DESCRIPTION ==

WPH Pixel is used to analyze the activities of users visiting the website (conversion analysis). The collected data can be used, for example, to analyze the effectiveness of the conversion path and build a sales funnel.

= FEATURES =

Data from WP codes are used for:

* Increasing the number of conversions. WP has the ability to optimize the displayed products so that the Partner obtains the largest possible number of conversions and sales.
* Determining user engagement: We examine the degree of user engagement in the content on the client's website. We segment users into groups including: "abandoned carts", "abandoned products".
* Personalizing product offers for users: Tools created by WP match the display of products to the right users who visited the partner's website or took an expected action on the site. Thanks to this, the Partner's products can effectively compete with other stores within the framework of personalization mechanisms.

More info: https://ads.wp.pl/help/pixel/

= HOW DOES THE WPH PIXEL SCRIPT WORK? =

The WP Pixel script is a code written in JavaScript that records the following user actions on the website:

* ViewContent – On the home page, on product category pages and on pages with specific products (product cards).
* Purchase – invoked on the purchase confirmation page (the last step in the purchasing path). The code can be embedded after completing the payment, on the so-called ThankYouPage immediately after returning from the payment provider or on the order confirmation page.
* AddToCart – On the page/event of adding products to the cart (e.g. click, displaying the landing page after clicking the "Add to cart" button)

= PARAMETERS =

Parameters are objects passed in JSON. They provide additional information about the activities of users visiting the website.

To add parameters to an object, format the data as an object with JSON and include it as the third parameter of the function when calling wph(\'track\').

= LIST OF PARAMETERS =

- **value** (type: number)
Order value
example: 104.99

- **currency** (type: string)
The default value is set in PLN, if the transaction took place in another currency, you can add information in which currency,
example: 'EUR'

- **name** (type: string)
product name
example: ’coca-cola’

- **content_category** (type: string)
Category name,
example: ’soda’

- **content_ids** (type: string[])
An array of product IDs provided in the product feed,
example: ['ID1', 'ID2', 'ID3']|

- **transaction_id** (type: string)
Unique order ID
example: 'ID001'

- **mvalue** (type: string)
Margin on a given click
example: ’15.50’

- **in_stock** (type: bolean)
Information whether the product is available
example: true

- **price** (type: number)
Price of a single product
example: 123.90

- **sizes** (type: string[])
Size chart assigned to the product
example: [’s’, ’m’, ’l’, ’xl’]

- **shipping_cost** (type: number)
Shipping cost
example 22.55

- **discount_code** (type : string)
Discount code
example: ’QWERTY123’

- **quantity** (type: number)
Number of units
example: 3

- **contents** (type: CustomProduct[])
List of products with identifiers associated with the product feed and additional informations.|
example:
`[
    {
        id: 'PRODUCT_ID',
        name: 'PRODUCT_NAME',
        price: 20.15,
        quantity: 2,
        in_stock: true,
        sizes: ['SX', 'L']
    }
]`

= VERIFICATION =

After installing the pixel on your website, open any page of the website where the pixel is located. If the pixel is installed correctly, it will send information to analytical systems.

== Installation ==

= Installation =

Option 1: Plugin Search Method

1. In the WordPress admin panel, navigate to "Plugins" and then click on "Add New."
2. In the search bar, enter "Wirtualna Polska Pixel" and look for the plugin by Wirtualna Polska Media S.A.
3. Select the correct plugin, named "WordPress WP Pixel," and click "Install."
4. Once installed, activate the plugin.
5. Go to the plugin settings and set your "WP Pixel ID."

Option 2: Download and Upload Method

1. Download the "Wirtualna Polska Pixel" plugin from the official WordPress site.
2. In the WordPress admin panel, go to "Plugins" and click on "Add New."
3. Choose "Upload Plugin," select the zip file you downloaded, and click "Install Now".
4. After installation, activate the plugin.
5. Access the plugin settings and configure your "WP Pixel ID."

Option 3: FTP Method

1. Download the "wirtualna Polska Pixel" plugin from the official WordPress site and unpack the files on your computer.
2. Use an FTP client to connect to your server.
3. Navigate to the "wp-content/plugins" directory and upload the entire "wirtualnapolska-pixel" folder.
4. In the WordPress admin panel, go to "Plugins" and activate the "WordPress WP Pixel" plugin.
5. Go to the plugin settings and set your "WP Pixel ID."

Ensure users follow the appropriate set of instructions based on their preferred installation method.

== SCREENSHOTS ==
1. Wirtualna Polska Pixel - plugin configuration

== CHANGELOG ==
= Wirtualna Polska Pixel 1.0.0 =
* Initial release

= Wirtualna Polska Pixel 1.0.1 =
* Add to cart with redirect to basket

= Wirtualna Polska Pixel 1.2.0 =
* Multilingual support

= Wirtualna Polska Pixel 1.2.1 =
* readme.txt in english

= Wirtualna Polska Pixel 1.2.2 =
* readme.txt modifications

= CONTRIBUTORS AND DEVELOPERS =

“Wirtualna Polska Pixel” is open source software. The following people have contributed to this plugin.

https://profiles.wordpress.org/wppixel/



