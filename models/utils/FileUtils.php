<?php


namespace app\models\utils;


use Yii;

class FileUtils
{

    public static function setUpdateInProgress(): void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, '1');
    }

    public static function setUpdateFinished(): void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, '0');
    }

    public static function isUpdateInProgress(): bool
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        if (is_file($file)) {
            $content = file_get_contents($file);
            if ((bool)$content) {
                // проверю, что с момента последнего обновления прошло не больше 15 минут. Если больше- сброшу флаг
                $lastTime = self::getLastUpdateTime();
                return !(time() - $lastTime > 900);
            }
            return false;
        }
        return false;
    }


    public static function getLastUpdateTime(): int
    {
        $file = Yii::$app->basePath . '\\priv\\last_update_time.conf';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 0;
    }
}