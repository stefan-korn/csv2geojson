<?php

namespace StefanKorn\CSV2GeoJSON;

use League\Csv\Reader;

class Csv2GeoJsonConverter {

  /**
   * @var array column(s) that contain geo data (coordinates)
   */
  protected $geoColumns = [];

  /**
   * @var array header columns
   */
  protected $csvHeader = [];

  /**
   * @var array header columns for comparing case insensitive
   */
  protected $csvHeaderCaseInsensitive = [];

  /**
   * @var array basic GeoJSON
   */
  protected $geoJson = [
    'type' => 'FeatureCollection',
    'features' => [],
  ];

  /**
   * @var string[] default columns to search for if no columns are specified
   */
  protected $defaultGeoColumns = [
    'lat',
    'lon',
    'latitude',
    'longitude',
    'latlon',
    'coordinates',
    'geopoint',
    'geopunkt',
    'koordinaten',
    'breitengrad',
    'längengrad',
  ];

  /**
   * @var \League\Csv\Reader
   */
  protected $reader;

  /**
   * convert CSV string to GeoJSON Feature Collection
   *
   * @param $csv_string
   * @param $name
   * @param $geo_columns
   * @param $delimiter
   * @param $header_offset
   *
   * @return false|string
   * @throws \League\Csv\Exception
   */
  public function convert($csv_string, $name = FALSE, $geo_columns = [], $delimiter = FALSE, $header_offset = 0) {
    if ($name) {
      $this->setFeatureCollectionName($name);
    }
    $this->reader = Reader::createFromString($csv_string);
    if ($delimiter) {
      $this->reader->setDelimiter($delimiter);
    }
    $this->reader->setHeaderOffset($header_offset);
    $this->csvHeader = $this->reader->getHeader();
    $this->setCaseInsensitiveCsvHeader();
    if (empty($geo_columns)) {
      $this->checkDefaultGeoColumns();
    }
    else {
      $this->geoColumns = $this->checkCaseGeoColumns($geo_columns);
    }
    foreach ($this->reader->getRecords() as $record) {
      $this->geoJson['features'][] = $this->getFeature($record);
    }
    return json_encode($this->geoJson);
  }

  /**
   * default geo column setter
   *
   * @param $default_geo_columns
   *
   * @return void
   */
  public function setDefaultGeoColumns($default_geo_columns) {
    $this->defaultGeoColumns = $default_geo_columns;
  }

  /**
   * set a name on the feature collection, fully optional.
   *
   * @param $name
   *
   * @return void
   */
  public function setFeatureCollectionName($name) {
    $this->geoJson['name'] = $name;
  }


  /**
   * set case-insensitive CSV header for comparing case-insensitive.
   *
   * @return void
   */
  protected function setCaseInsensitiveCsvHeader() {
    foreach ($this->csvHeader as $column) {
      $this->csvHeaderCaseInsensitive[trim(strtolower($column))] = $column;
    }
  }

  /**
   * check case insensitive for geo columns
   *
   * @param $geo_columns
   *
   * @return array
   */
  protected function checkCaseGeoColumns($geo_columns) {
    $columns = [];
    foreach ($geo_columns as $geo_column) {
      if (in_array(trim(strtolower($geo_column)), array_flip($this->csvHeaderCaseInsensitive))) {
        $columns[] = $this->csvHeaderCaseInsensitive[trim(strtolower($geo_column))];
      }
    }
    return $columns;
  }

  /**
   * check and set default geo columns if any are found in CSN header
   *
   * @return void
   */
  protected function checkDefaultGeoColumns() {
    foreach ($this->defaultGeoColumns as $default_geo_column) {
      if (count($this->geoColumns) < 2) {
        if (in_array(trim(strtolower($default_geo_column)), array_flip($this->csvHeaderCaseInsensitive))) {
          $this->geoColumns[] = $this->csvHeaderCaseInsensitive[trim(strtolower($default_geo_column))];
        }
      }
    }
  }

  /**
   * get GeoJSON feature for csv record.
   *
   * @param $record
   * @param $geometry_type
   *
   * @return array
   */
  protected function getFeature($record, $geometry_type = 'Point') {
    return [
      'type' => 'Feature',
      'geometry' => [
        'type' => $geometry_type,
        'coordinates' => $this->getCoordinates($record),
      ],
      'properties' => $this->getProperties($record),
    ];
  }

  /**
   * get GeoJSON feature properties from record, any columns from CSV header
   * which are not defined as columns containing GeoJSON geometry/coordinates
   *
   * @param $record
   *
   * @return array
   */
  protected function getProperties ($record) {
    $properties = [];
    foreach ($this->csvHeader as $column) {
      if (!in_array($column, $this->geoColumns)) {
        $properties[$column] = $record[$column];
      }
    }
    return $properties;
  }

  /**
   * get GeoJSON feature coordinates from columns defined containing
   * GeoJSON geometry/coordinates
   *
   * @param $record
   *
   * @return array
   */
  protected function getCoordinates($record) {
    switch (count($this->geoColumns)) {
      case 1:
        $geocolumn = reset($this->geoColumns);
        return array_map('floatval', explode(',', $record[$geocolumn]));
      case 2:
        $coordinates = [];
        foreach ($this->geoColumns as $geocolumn) {
          $coordinates[] = floatval($record[$geocolumn]);
        }
        return $coordinates;
    }
    return [];
  }
}
