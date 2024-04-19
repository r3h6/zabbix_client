<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * A Operation which returns the current TYPO3 version
 */
#[MonitoringOperation('GetTYPO3Version')]
class GetTYPO3Version implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (((int)($parameter['asInteger'] ?? 0)) === 1) {
            return new OperationResult(true, VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()));
        }
        return new OperationResult(true, VersionNumberUtility::getCurrentTypo3Version());
    }
}
