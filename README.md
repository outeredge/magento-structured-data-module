[![Packagist](https://img.shields.io/packagist/v/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dt/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dm/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)

# outer/edge Structured Data Module for Magento 2

[Hyvä](https://hyva.io) and [Breeze](https://breezefront.com/) compatible.

Our open source module allows you to quickly add structured data markup (also known as Rich Snippets) to any Magento 2 store by simply installing our module and setting a few configuration options. 

Once this module is installed you will have valid structured data in the source of your product, contact and CMS pages. For example:
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

### Product Page (GraphQL available)

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

#### Products

![structured_data-product](/assets/config-product.png)

* **Enable:** Enable or disable structured data on product pages.
* **Use Short Description:** Use `short_description` attribute for the `description` markup. By default `description` will be used.
* **Include ChildProducts:** Choose whether to include individual offer for each child (simple) product for structured data on configurable product pages.
* **Include Product Weights:** Ad `weight` schema to product page structured data.
* **Product Brand/Manufacturer field:** Choose which Magento attribute is used to populate the structured data values.
  - **Brand** (Default: `manufacturer` or `brand`)
  - **MPN** (Default: empty)
  - **ISBN** (Default: empty)
  - **Size** (Default: empty)
  - **GTIN** (Default: empty)
  - **Color** (Default: `Color` or `Colour`)
  - **Material** (Default: empty)
  - **Keywords** (Default: empty)

#### CMS Pages

![structured_data-cms](/assets/config-cms.png)

* **Enable:** Enable or disable structured data on CMS pages.
* **Enable About Page:** Enable or disable `"@type": "AboutPage"`.
* **About Page:** Select the CMS page for  `"@type": "AboutPage"`.

#### Organization

![structured_data-contact](/assets/config-organization.png)

* **Type:** Select whether business in a Local Business or Organization.
* **Latitude:** Specify latitude for local business.
* **Longitude:** Specify longitude for local business.
* **Enable on Home Page:** Enable or disable Organization structured data on Home page.
* **Enable on Contact Page:** Enable or disable Organization structured data on Contact page.
* **Related Pages** Populates "SameAs" property. Add links to related pages, for example Facebook, Linked In and other social media sites.

Once the module is installed and configured you will find the schema markup in your source code:

![schema_screenshot](/assets/screenshot-schema.png)

## GraphQL

Our structured data module provides for product schema to the built in Magento GraphGL endpoint. Simply request the `structured_data` field with your product data as per the example below and the data will be returned as a JSON array:

```
{
  products(
    filter: {
        ...
    }
  ) {
    items {
      sku
      name
      structured_data
    }
  }
}
```


## Uninstalling the module

####  Remove via Composer

```
composer remove outeredge/magento-structured-data-module
```

### References

#### Google docs for structured data format (using JSON-LD format)
https://developers.google.com/search/docs/guides/intro-structured-data
https://developers.google.com/search/docs/advanced/structured-data/product

#### Structured data syntax is based on
http://schema.org/
