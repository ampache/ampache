<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

/**
 * supported-report-set property.
 *
 * This property is defined in RFC3253, but since it's
 * so common in other webdav-related specs, it is part of the core server.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedReportSet extends DAV\Property {

    /**
     * List of reports
     *
     * @var array
     */
    protected $reports = [];

    /**
     * Creates the property
     *
     * Any reports passed in the constructor
     * should be valid report-types in clark-notation.
     *
     * Either a string or an array of strings must be passed.
     *
     * @param mixed $reports
     */
    function __construct($reports = null) {

        if (!is_null($reports))
            $this->addReport($reports);

    }

    /**
     * Adds a report to this property
     *
     * The report must be a string in clark-notation.
     * Multiple reports can be specified as an array.
     *
     * @param mixed $report
     * @return void
     */
    function addReport($report) {

        if (!is_array($report)) $report = [$report];

        foreach($report as $r) {

            if (!preg_match('/^{([^}]*)}(.*)$/',$r))
                throw new DAV\Exception('Reportname must be in clark-notation');

            $this->reports[] = $r;

        }

    }

    /**
     * Returns the list of supported reports
     *
     * @return array
     */
    function getValue() {

        return $this->reports;

    }

    /**
     * Returns true or false if the property contains a specific report.
     *
     * @param string $reportName
     * @return bool
     */
    function has($reportName) {

        return in_array(
            $reportName,
            $this->reports
        );

    }

    /**
     * Serializes the node
     *
     * @param DAV\Server $server
     * @param \DOMElement $prop
     * @return void
     */
    function serialize(DAV\Server $server, \DOMElement $prop) {

        foreach($this->reports as $reportName) {

            $supportedReport = $prop->ownerDocument->createElement('d:supported-report');
            $prop->appendChild($supportedReport);

            $report = $prop->ownerDocument->createElement('d:report');
            $supportedReport->appendChild($report);

            preg_match('/^{([^}]*)}(.*)$/',$reportName,$matches);

            list(, $namespace, $element) = $matches;

            $prefix = isset($server->xmlNamespaces[$namespace])?$server->xmlNamespaces[$namespace]:null;

            if ($prefix) {
                $report->appendChild($prop->ownerDocument->createElement($prefix . ':' . $element));
            } else {
                $report->appendChild($prop->ownerDocument->createElementNS($namespace, 'x:' . $element));
            }

        }

    }

}
