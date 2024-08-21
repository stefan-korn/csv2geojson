# CSV2GeoJSON - convert CSV to GeoJSON

Converts [CSV](http://en.wikipedia.org/wiki/Comma-separated_values) into [GeoJSON](http://www.geojson.org/)

can only handle point geometries currently.

## Usage example

```php
$converter = new \StefanKorn\CSV2GeoJSON\Csv2GeoJsonConverter();

$geo_json = $converter->->convert(file_get_contents('https://opendata.duesseldorf.de/sites/default/files/Bezirksverwaltungsstellen%20in%20D%C3%BCsseldorf_0.csv'), FALSE, [], ';');
```
