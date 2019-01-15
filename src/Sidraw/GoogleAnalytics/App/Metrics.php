<?php

namespace Sidraw\GoogleAnalytics\App;

class Metrics extends AbstractSet
{
    private $metrics = [];

    /**
     * @return \Google_Service_AnalyticsReporting_Metric
     */
    public function getCollection(): \Google_Model
    {
        return new \Google_Service_AnalyticsReporting_Metric();
    }

    public function set(array $expressions)
    {
        foreach ($expressions as $expression => $alias) {
            $collection = $this->getCollection();

            if (is_numeric($expression)) {
                $expression = $alias;
                $alias = null;
            }

            $collection->setExpression("ga:{$expression}");

            if ( ! is_null($alias)) {
                $collection->setAlias($alias);
            }

            $this->metrics[] = $collection;
        }
    }

    public function get(): array
    {
        return $this->metrics;
    }
}