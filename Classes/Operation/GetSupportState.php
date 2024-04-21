<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;
use WapplerSystems\ZabbixClient\Service\CoreVersionService;


/**
 *
 */
#[MonitoringOperation('GetSupportState')]
class GetSupportState implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = []): OperationResult
    {

        /** @var CoreVersionService $coreVersionService */
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);

        // No updates for development versions
        if (!$coreVersionService->isInstalledVersionAReleasedVersion()) {
            return new OperationResult(true, '');
        }

        try {
            $versionMaintenanceWindow = $coreVersionService->getMaintenanceWindow();
        } catch (RemoteFetchException $remoteFetchException) {
            return new OperationResult(false, false);
        }

        if ($versionMaintenanceWindow->isSupportedByCommunity()) {
            return new OperationResult(true, 'community');
        }

        if ($versionMaintenanceWindow->isSupportedByElts()) {
            return new OperationResult(true, 'elts');
        }
        return new OperationResult(true, 'outdated');
    }
}
