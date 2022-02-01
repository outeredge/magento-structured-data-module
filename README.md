[![Packagist](https://img.shields.io/packagist/v/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dt/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)
[![Packagist](https://img.shields.io/packagist/dm/outeredge/magento-structured-data-module?style=for-the-badge)](https://packagist.org/packages/outeredge/magento-structured-data-module)

# outer/edge Structured Data Module for Magento 2

Our open source module allows you to quickly add structured data markup to any Magento 2 store by simply installing our module and setting a few configuration options. The module provides the following structured data:

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

##### Stuctured Data (Product)

* **Enable:** Enable or disable structured data on product pages.
* **Use Short Description:** Use `short_description` attribute for the `description` markup. By default `description` will be used.
* **Include ChildProducts:** Choose whether to include indidual offer for each child (simple) product for structured data on configurable product pages.
* **Include Product Weights:** Ad `weight` schema to product page strutured data.
* **Product Brand/Manufacturer field:** Choose which Magento attribute is used for the `brand` schema. If not specified the module will automatically check for `manufacturer` and `brand` attributes.

##### Structured Data (CMS Page)

* **Enable:** Enable or disable structured data on CMS pages.
* **Enable About Page:** Enable or disable `"@type": "AboutPage"`.
* **About Page:** Select the CMS page for  `"@type": "AboutPage"`.

##### Structured Data (Contact Page)

* **Enable:** Enable or disable structured data on Contact page.
* **Type:** Select whether business in a Local Business or Organization.
* **Latitude:** Specify latitude for local business.
* **Longitude:** Specify longitude for local business.

### References

#### Google docs for structured data format (using JSON-LD format)
https://developers.google.com/search/docs/guides/intro-structured-data

#### Structured data syntax is based on
http://schema.org/
