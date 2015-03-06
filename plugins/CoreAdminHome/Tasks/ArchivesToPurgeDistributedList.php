<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CoreAdminHome\Tasks;

use Piwik\Concurrency\DistributedList;
use Piwik\Date;

/**
 * Distributed list that holds a list of year-month archive table identifiers (eg, 2015_01 or 2014_11). Each item in the
 * list is expected to identify a pair of archive tables that contain invalidated archives.
 *
 * The archiving purging scheduled task will read items in this list when executing the daily purge.
 *
 * This class is necessary in order to keep the archive purging scheduled task fast. W/o a way to keep track of
 * tables w/ invalid data, the task would have to iterate over every table, which is not desired for a task that
 * is executed daily.
 *
 * If users find other tables contain invalidated archives, they can use the core:purge-old-archive-data command
 * to manually purge them.
 */
class ArchivesToPurgeDistributedList extends DistributedList
{
    const OPTION_INVALIDATED_DATES_SITES_TO_PURGE = 'InvalidatedOldReports_DatesWebsiteIds';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(self::OPTION_INVALIDATED_DATES_SITES_TO_PURGE);
    }

    /**
     * @inheritdoc
     */
    public function setAll($yearMonths)
    {
        $yearMonths = array_unique($yearMonths);
        parent::setAll($yearMonths);
    }

    public function getAllAsDates()
    {
        $dates = array();
        foreach ($this->getAll() as $yearMonth) {
            try {
                $date = Date::factory(str_replace('_', '-', $yearMonth) . '-01');
            } catch (\Exception $ex) {
                continue; // invalid year month in distributed list
            }

            $dates[] = $date;
        }
        return $dates;
    }

    public function removeDate(Date $date)
    {
        $yearMonth = $date->toString('Y_m');
        $this->remove($yearMonth);
    }
}