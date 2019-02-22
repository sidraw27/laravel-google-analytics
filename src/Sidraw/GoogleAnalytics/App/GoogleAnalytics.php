<?php

namespace Sidraw\GoogleAnalytics\App;

class GoogleAnalytics
{
    private $analytic;
    /** @var AbstractSet $metrics */
    private $metrics;
    /** @var AbstractSet $dimensions */
    private $dimensions;
    /** @var \Google_Service_AnalyticsReporting_ReportRequest $request */
    private $request;

    /**
     * GoogleAnalytics constructor.
     * @param Metrics $metrics
     * @param Dimensions $dimensions
     * @throws \Exception
     */
    public function __construct()
    {
        try {
            $this->initializeAnalytics();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param string $startDate
     * @param string $endDate
     * @param array $metrics
     * @param array $dimensions
     * @param array $filters
     * @param string $preg
     * @return array
     * @throws \Exception
     */
    public function getReport(
        string $startDate,
        string $endDate,
        array $metrics,
        array $dimensions,
        array $filters = [],
        ?string $preg = null
    ) {
        if (empty($metrics) || empty($dimensions)) {
            throw new \Exception('metrics and dimension is requirement');
        }

        $this->setDateRange($startDate, $endDate);

        $this->metrics->set($metrics);
        $this->dimensions->set(array_keys($dimensions));

        foreach ($filters as $type => $filter) {
            $this->setFilterClause($type, $filter);
        }

        $this->request->setMetrics($this->metrics->get());
        $this->request->setDimensions($this->dimensions->get());

        $body = \App::make(\Google_Service_AnalyticsReporting_GetReportsRequest::class);
        $body->setReportRequests($this->request);

        $response = $this->analytic->reports->batchGet($body);

        return $this->parseResult($response, array_values($dimensions));
    }

    /**
     * @throws \Exception
     */
    private function initializeAnalytics()
    {
        try {
            $config = $this->getConfig();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $this->metrics    = Metrics::getInstance();
        $this->dimensions = Dimensions::getInstance();

        $this->request = \App::make(\Google_Service_AnalyticsReporting_ReportRequest::class);

        $this->request->setViewId($config['viewId']);

        $client = new \Google_Client();
        $client->setApplicationName($config['appName']);

        try {
            $client->setAuthConfig($config['key']);
        } catch (\Google_Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->analytic = \App::makeWith(\Google_Service_AnalyticsReporting::class, ['client' => $client]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getConfig()
    {
        $configs = [
            'viewId'  => \Config::get('google-analytics.viewId', null),
            'appName' => \Config::get('google-analytics.name', null),
            'key'     => \Config::get('google-analytics.key', null)
        ];

        foreach ($configs as $name => $config) {
            if (is_null($config)) {
                throw new \Exception("{$name} is requirement");
            }
        }

        return $configs;
    }

    private function setDateRange($startTime, $endTime)
    {
        $startTime = $this->isTimestamp($startTime) ? date('Y-m-d', $startTime) : $startTime;
        $endTime   = $this->isTimestamp($endTime) ? date('Y-m-d', $endTime) : $endTime;

        $dateRange = \App::make(\Google_Service_AnalyticsReporting_DateRange::class);

        $dateRange->setStartDate($startTime);
        $dateRange->setEndDate($endTime);

        $this->request->setDateRanges($dateRange);
    }

    private function isTimestamp($time)
    {
        $check = (is_int($time) || is_float($time)) ? $time : (string)(int) $time;

        return ($check === $time) && ((int)$time <= PHP_INT_MAX) && ((int)$time >= ~PHP_INT_MAX);
    }

    private function setFilterClause(string $type, array $expressions, string $operator = 'AND')
    {
        switch ($type) {
            case 'dimension':
                $filters = [];
                foreach ($expressions as $dimensionName => $expression) {
                    $filter = new \Google_Service_AnalyticsReporting_DimensionFilter();
                    $filter->setDimensionName("ga:{$dimensionName}");
                    // Todo: 需修改可自定義operator
                    if (is_array($expression)) {
                        $filter->setOperator('REGEXP');
                        $filter->setExpressions('(' . implode('|', $expression) . ')');
                    } else {
                        $filter->setOperator('PARTIAL');
                        $filter->setExpressions($expression);
                    }

                    $filters[] = $filter;
                }

                $dimensionFilterClause = new \Google_Service_AnalyticsReporting_DimensionFilterClause();
                $dimensionFilterClause->setOperator($operator);
                $dimensionFilterClause->setFilters($filters);
                $this->request->setDimensionFilterClauses($dimensionFilterClause);
                break;
        }
    }

    private function parseResult(\Google_Service_AnalyticsReporting_GetReportsResponse $responses, array $dimensionRule = null)
    {
        $result = [];

        foreach ($responses as $report) {
            /** @var \Google_Service_AnalyticsReporting_ColumnHeader $header */
            $header        = $report->getColumnHeader();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows          = $report->getData()->getRows();

            /** @var \Google_Service_AnalyticsReporting_ReportRow $row */
            foreach ($rows as $row) {
                $dimensions = $row->getDimensions();

                $dimensionNestedKeys = [];
                foreach ($dimensions as $key => $dimension) {
                    $rule = $dimensionRule[$key];
                    if (is_null($rule) || is_bool($rule) && ! $rule) continue;

                    if (preg_match('/^\/+.*\/{1}$/', $rule)) {
                        preg_match($rule, $dimension, $match);
                        if ( ! isset($match[1])) {
                            continue 2;
                        }
                        $dimensionNestedKeys[] = $match[1];
                    } else {
                        $dimensionNestedKeys[] = $rule;
                    }
                }

                $metrics  = $row->getMetrics();
                $metricsResult = [];
                foreach ($metrics as $metric) {
                    foreach ($metric->getValues() as $index => $value) {
                        $metricName = $metricHeaders[$index]->getName();

                        if (isset($metricsResult[$metricName])) {
                            $metricsResult[$metricName] += $value;
                        } else {
                            $metricsResult[$metricName] = (int) $value;
                        }
                    }
                }

                $tmpArr = [];
                $tmp = &$tmpArr;
                foreach ($dimensionNestedKeys as $index => $key) {
                    if (empty($tmp[$key])) {
                        $tmp[$key] = key(array_slice($dimensionNestedKeys,-1,1,TRUE)) === $index ? $metricsResult : [];
                        $tmp = &$tmp[$key];
                    }
                }
                $result = array_merge_recursive($result, $tmpArr);
            }
        }

        return $result;
    }
}