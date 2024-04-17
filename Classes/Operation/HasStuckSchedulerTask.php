<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use DateTime;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('HasStuckSchedulerTask')]
class HasStuckSchedulerTask implements IOperation, SingletonInterface
{
    private const MAX_RUNNING_HOURS = 6;

    /**
     * @param array $parameter
     * @return OperationResult
     *
     */
    public function execute(array $parameter = []): OperationResult
    {
        $maxRunningHours = (int)(isset($parameter['maxRunningHours']) ? $parameter['maxRunningHours'] : 0);
        // Make sure we do not use a number smaller than 1 here.
        if ($maxRunningHours < 1) {
            $maxRunningHours = self::MAX_RUNNING_HOURS;
        }

        $schedulerRecords = $this->fetchSchedulerTasks();
        if (0 === count($schedulerRecords)) {
            // No tasks found.
            return new OperationResult(true, false);
        }

        foreach ($schedulerRecords as $schedulerRecord) {
            // Check if the task is running.
            $isRunning = !empty($schedulerRecord['serialized_executions']);
            if (!$isRunning) {
                continue;
            }
            // Validate for required column value (lastexecution_time).
            if (empty($schedulerRecord['lastexecution_time'])) {
                continue;
            }

            // Compare lastexecution_time with current time.
            $currentDateTime = new DateTime();
            $lastExecutedDateTime = (new DateTime())->setTimestamp((int)$schedulerRecord['lastexecution_time']);
            $runningHours = (int)$currentDateTime->diff($lastExecutedDateTime)->format('%h');
            $runningHours += (int)$currentDateTime->diff($lastExecutedDateTime)->days * 24;

            if ($runningHours >= $maxRunningHours) {
                return new OperationResult(true, true);
            }
        }

        // No task is running.
        return new OperationResult(true, false);
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function fetchSchedulerTasks(): array
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder
            ->select('uid', 'serialized_executions', 'lastexecution_time')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->andWhere(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
}
