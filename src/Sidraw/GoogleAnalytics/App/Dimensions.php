<?php

namespace Sidraw\GoogleAnalytics\App;

class Dimensions extends AbstractSet
{
    /**
     * @return \Google_Service_AnalyticsReporting_Dimension
     */
    public function getCollection(): \Google_Model
    {
        return new \Google_Service_AnalyticsReporting_Dimension();
    }

    public function set(array $expressions)
    {
        foreach ($expressions as $name) {
            $collect = $this->getCollection();

            $collect->setName("ga:{$name}");

            $this->collection[] = $collect;
        }
    }
}