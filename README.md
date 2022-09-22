[![Packagist](https://img.shields.io/packagist/v/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dt/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dm/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)

# outer/edge Structured Data Module for Magento 2

Our open source module allows you to quickly add structured data markup to any Magento 2 store by simply installing our module and setting a few configuration options. Once this module is installed you will have valid structured data in the source of your product, contact and CMS pages. For example: 
https://developers.google.com/search/docs/advanced/structured-data/product

This will look similar to the below:

```
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "@id": "https://example.co.uk/blue-t-shirt#Product",
    "brand": {
            "@type": "Brand",
            "name": "Nike"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "bestRating": "100",
        "worstRating": "1",
        "ratingValue": "4.55",
        "reviewCount": "5"
    },
    "name": "Nike Blue T-Shirt",
    "sku": "blue-t-shirt",
    "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer viverra vitae nulla quis venenatis. Donec sollicitudin pharetra eros, in facilisis justo fringilla eu. In at consequat felis.",
    "image": "https://example.co.uk/media/catalog/product/b/t/blue-t-shirt.jpg",
    "offers": {
        "@type": "Offer",
        "url": "https://example.co.uk/blue-t-shirt",
        "price": 18.99,
        "priceCurrency": "GBP",
        "priceSpecification": {
            "@type": "UnitPriceSpecification",
            "price": 18.99,
            "priceCurrency": "GBP",
            "valueAddedTaxIncluded": true
        },
        "availability": "http://schema.org/InStock",
        "itemCondition": "http://schema.org/NewCondition"
    }
}
</script>
```

The module provides the following structured data:

### Product Page

* @type
* @id
* name
* sku
* description
* image
* weight
* brand
* aggregateRating
  * bestRating
  * worstRating
  * ratingValue
  * reviewCount
* mpn
* material
* color
* price
* priceCurency
* valueAddedTaxIncluded
* availability
* itemCondition
* AggregateOffer
  * offers
* highPrice
* lowPrice

### Contact Page

* @type
* @id
* name
* image
* address
* telephone
* email
* url
* geo

### CMS Page

* name
* mainContentOfPage
* description
* primaryImageOfPage

## Installation

#### Install via Composer

```
composer require outeredge/magento-structured-data-module
```

#### Review configuration for Structure Data Module

Configuration is available in `Stores > Configuration > outer/edge > Structured Data`. The following options are available:

#### Structured Data (Product)

![structured_data-product](https://user-images.githubusercontent.com/2035088/152131539-a2e320b4-b819-4c62-b42f-df96c4fd7872.png)

* **Enable:** Enable or disable structured data on product pages.
* **Use Short Description:** Use `short_description` attribute for the `description` markup. By default `description` will be used.
* **Include ChildProducts:** Choose whether to include individual offer for each child (simple) product for structured data on configurable product pages.
* **Include Product Weights:** Ad `weight` schema to product page structured data.
* **Product Brand/Manufacturer field:** Choose which Magento attribute is used for the `brand` schema. If not specified the module will automatically check for `manufacturer` and `brand` attributes.

#### Structured Data (CMS Page)

![structured_data-cms](https://user-images.githubusercontent.com/2035088/152131708-ba038f9f-7f94-4654-9128-6d861fd1b397.png)

* **Enable:** Enable or disable structured data on CMS pages.
* **Enable About Page:** Enable or disable `"@type": "AboutPage"`.
* **About Page:** Select the CMS page for  `"@type": "AboutPage"`.

#### Structured Data (Contact Page)

![structured_data-contact](https://user-images.githubusercontent.com/2035088/152131796-563d33b1-2721-4727-b278-b7490fe6920d.png)

* **Enable:** Enable or disable structured data on Contact page.
* **Type:** Select whether business in a Local Business or Organization.
* **Latitude:** Specify latitude for local business.
* **Longitude:** Specify longitude for local business.


Once the module is installed and configured you will find the schema markup in your source code:

![schema_screenshot](https://user-images.githubusercontent.com/2035088/152323033-7e48e3b4-4e72-4d72-9b92-8356ac38fe91.png)

### References

#### Google docs for structured data format (using JSON-LD format)
https://developers.google.com/search/docs/guides/intro-structured-data
https://developers.google.com/search/docs/advanced/structured-data/product

#### Structured data syntax is based on
http://schema.org/
