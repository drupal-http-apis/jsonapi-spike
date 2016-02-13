<?php

namespace Drupal\jsonapi;

class DocumentContext {
    public function __construct($data, $config, $options) {
        $this->data = $data;
        $this->config = $config;
        $this->meta = null;
        $this->options = $options;
        $this->included = [];
    }
    public function addIncluded($record) {
        $type = $record['type'];
        $id = $record['id'];
        if (!isset($this->included[$type])) {
            $this->included[$type] = [];
        }
        if (!isset($this->included[$type][$id])) {
            $this->included[$type][$id] = $record;
        }
    }
    public function shouldInclude($path, $defaultInclude) {
        if (isset($this->options['include'])) {
            $include = $this->options['include'];
        } else {
            $include = $defaultInclude;
        }
        foreach($include as $included) {
            if ($path == array_slice($included, 0, count($path))) {
                return true;
            }
        }
    }
    protected function endpointFor($entityType, $bundleId) {
        if (isset($this->config['scope']['bundles'][$entityType][$bundleId])) {
            return $this->config['scope']['bundles'][$entityType][$bundleId];
        }
        if (isset($this->config['scope']['entities'][$entityType])) {
            return $this->config['scope']['entities'][$entityType];
        }
    }
    protected function configFor($entityType, $bundleId) {
        $endpointConfig = $this->config['scope']['endpoints'][$this->endpointFor($entityType, $bundleId)];
        if (isset($endpointConfig['extensions'][$bundleId])) {
            return $endpointConfig['extensions'][$bundleId];
        } else {
            return $endpointConfig;
        }
    }
    public function fieldsFor($entityType, $bundleId) {
        return $this->configFor($entityType, $bundleId)['fields'];
    }
    public function defaultIncludeFor($entityType, $bundleId) {
        return $this->configFor($entityType, $bundleId)['defaultInclude'];
    }


    public function shouldIncludeField($type, $field) {
        return $field == 'id' || !isset($this->options['fields'][$type]) || in_array($field, $this->options['fields'][$type]);
    }

    public function allIncluded() {
        $output = [];
        foreach($this->included as $type => $values) {
            foreach($values as $id => $value) {
                $output[] = $value;
            }
        }
        return $output;
    }
    public function debugEnabled() {
        return $this->options['debug'];
    }
    public function addMeta($key, $value) {
        if (!$this->meta) {
            $this->meta = [];
        }
        $this->meta[$key] = $value;
    }

    public function meta() {
        if ($this->debugEnabled()) {
            $this->addMeta('options', $this->options);
           $this->addMeta('config', $this->config);
        }
        return $this->meta;
    }
}