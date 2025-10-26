<?php
/**
 * @package       Task - JL Sitemap CRON
 * @version       @version@
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2025 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Task\Jlsitemapcron\Extension;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\JLSitemap\Site\Model\SitemapModel;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

use function count;
use function defined;

defined('_JEXEC') or die;

/**
 * A task plugin. For auto generate XML sitemap
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class Jlsitemapcron extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * @var string[]
     * @since 5.0.0
     */
    private const TASKS_MAP = [
        'plg_task_jlsitemapcron' => [
            'langConstPrefix' => 'PLG_TASK_JLSITEMAPCRON',
            'method'          => 'generateMap',
        ],
    ];
    /**
     * @var bool
     * @since 5.0.0
     */
    protected $autoloadLanguage = true;


    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }


    /**
     * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
     *
     * @return int  The routine exit code.
     *
     * @throws Exception
     * @since  5.0.0
     */
    private function generateMap(ExecuteTaskEvent $event): int
    {
        /** @var Registry $params Current task params */
        $params = new Registry($event->getArgument('params'));
        /** @var int $task_id The task id */
        $task_id = $event->getTaskId();

        // Run generation
        /** @var SitemapModel $model */
        $model = $this->getApplication()
            ->bootComponent('com_jlsitemap')
            ->getMVCFactory()
            ->createModel('Sitemap', 'Site', ['ignore_request' => true]);

        try {
            if ($urls = $model->generate()) {
                $success = Text::sprintf(
                    'PLG_TASK_JLSITEMAPCRON_GENERATION_SUCCESS',
                    count($urls->includes),
                    count($urls->excludes),
                    count($urls->all)
                );
                $this->logTask($success);

                return Status::OK;
            }

            $this->logTask(Text::sprintf('PLG_TASK_JLSITEMAPCRON_GENERATION_FAILURE', $model->getError(), 'error'));

            return Status::KNOCKOUT;
        } catch (Exception $e) {
            $this->logTask(Text::sprintf('PLG_TASK_JLSITEMAPCRON_GENERATION_FAILURE', $e->getMessage(), 'error'));

            return Status::KNOCKOUT;
        }
    }
}
