# post_code_lookup
Provides a simple PHP-based post code lookup site that uses Geonames post code data.

## Post Code Data
The post code data is based on data derived from the [Geonames project](https://geonames.org/).
The default data set is for the USA.
If you want to add additional countries to the postcode database, proceed as follows:
* Substitute in place of *ISO2* the uppercase 2-digit country code containing the desired post code data.

```
src/import_postcode.sh ISO2
```
This utility populates the `data/post_code_lookup.sqlite` SQLite3 database.

## Simple Setup
You can run `simple_setup.sh` to install and configure PHP, PHP-FPM, needed PHP extensions, and nginx.
