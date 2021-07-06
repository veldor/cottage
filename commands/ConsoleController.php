<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Cottage;
use app\models\Utils;
use app\models\utils\FileUtils;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ConsoleController extends Controller
{
    /**
     * Актуализация данных об участках
     * @return int Exit code
     */
    public function actionRefreshMainData(): int
    {

        if (FileUtils::isUpdateInProgress()) {
            echo "try later\n";
            return ExitCode::OK;
        }
        FileUtils::setUpdateInProgress();
        $existedCottages = Cottage::getRegister();
        echo "start refresh\n";
        Utils::reFillFastInfo($existedCottages);
        echo "done\n";
        FileUtils::setUpdateFinished();
        return ExitCode::OK;
    }
}
