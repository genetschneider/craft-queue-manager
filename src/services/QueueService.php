<?php

namespace lukeyouell\queuemanager\services;

use lukeyouell\queuemanager\QueueManager;

use Craft;
use craft\base\Component;

use yii\db\Query;

class QueueService extends Component
{
    // Public Methods
    // =========================================================================

    public function getJobs($status = null)
    {
        $query = $this->_createJobQuery();

        switch ($status) {
            case 'pending':
                $query->where(['fail' => false, 'timeUpdated' => null]);
                break;

            case 'reserved':
                $query->where(['and', ['fail' => false], ['not', ['timeUpdated' => null]]]);
                break;

            case 'failed':
                $query->where(['fail' => true]);
                break;
        }

        $results = $query->all();

        $info = [];

        foreach ($results as $result) {
            $jobData = null;

            try {
                $jobData = unserialize($result['job']);
            } catch (\Exception $e) {
                $job = $result['job'];

                if (!('resource' === gettype($job) && 'stream' === get_resource_type($job))) {
                    throw $e;
                }

                $jobData = stream_get_contents($job);
            }

            $info[] = [
                'id'          => $result['id'],
                'data'        => $jobData,
                'description' => $result['description'],
                'timePushed'  => $result['timePushed'],
                'timeUpdated' => $result['timeUpdated'],
                'ttr'         => $result['ttr'],
                'priority'    => $result['priority'],
                'progress'    => $result['progress'],
                'fail'        => (int)$result['fail'],
                'dateFailed'  => $result['dateFailed'],
                'error'       => $result['error'],
            ];
        }

        return $info;
    }

    public function getJobCount()
    {
        return (new Query())
            ->from('{{%queue}}')
            ->count();
    }

    public function getJobIds()
    {
        return (new Query())
            ->from('{{%queue}}')
            ->select('id')
            ->all();
    }

    // Private Methods
    // =========================================================================

    private function _createJobQuery(): Query
    {
        $limit = $this->_getLimit();

        return (new Query())
            ->from('{{%queue}}')
            ->orderBy(['timePushed' => SORT_DESC])
            ->limit($limit);
    }

    private function _getSettings()
    {
        return QueueManager::getInstance()->getSettings();
    }

    private function _getLimit()
    {
        $limit = $this->_getSettings()->jobLimit ?? 200;

        if ($limit == 0) {
            $limit = null;
        }

        return $limit;
    }
}
