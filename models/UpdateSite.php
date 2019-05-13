<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.07.2018
 * Time: 12:16
 */

namespace app\models;


use ErrorException;
use RuntimeException;
use yii\base\Model;
use Yii;
use ZipArchive;

class UpdateSite extends Model
{

    public $version; // Версия обновления
    public $description; // Описание изменений в обновлении

    protected static $scanningDirectories = ['assets', 'config', 'controllers', 'models', 'public_html', 'views', 'widgets', 'validators'];
    protected $root;
    protected $updateRoot;
    protected $lastUpdateTime;
    protected $currentTime;
    protected $drive;
    protected $updates;

    public function rules():array
    {
        return [
            [['version', 'description'], 'required'],
        ];
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->drive = new Cloud;
        $this->currentTime = time();
        $this->root = str_replace('\\', '/', Yii::getAlias('@app'));
        $this->updateRoot = str_replace('\\', '/', Yii::getAlias('@app') . '\\site_update');
        // Ищу номер последней версии обновления
        if (is_file($this->updateRoot . '/site_version.txt')) {
            $file = fopen($this->updateRoot . '/site_version.txt', 'rb');
            $this->version = fgets($file);
        }
    }

    /**
     * @return array
     * @throws ErrorException
     */
    public function installUpdate():array
    {
        // Для начала- получу список обновлений
        $updates = $this->drive->getUpdates();
        $info = null;
        if (!empty($updates)) {
            $this->rmRec($this->updateRoot . '/downloaded_updates');
	        if (!mkdir($concurrentDirectory = $this->updateRoot . '/downloaded_updates', 0777, true) && !is_dir($concurrentDirectory)) {
		        throw new RuntimeException(sprintf('Папка "%s" не создана', $concurrentDirectory));
	        }
            foreach ($updates as $update) {
                // скачаю обновления
                $path = "Updates/$update";
                $destination = $this->updateRoot . '/downloaded_updates/';
                $name = $update;
                if (!$this->drive->downloadFile($path, $destination, $name)) {
	                throw new ErrorException('Ошибка при загрузке обновления!');
                }
                // установлю обновление
                $zip = new ZipArchive;
                if ($zip->open("$destination/$name") === TRUE) {
                    $info = explode("\r\n",$zip->getFromName('update_info.txt'));
                    $result = $zip->extractTo($this->root);
                    $zip->close();
                    if (!$result) {
	                    throw new ErrorException('Ошибка при копировании файлов!');
                    }
                } else {
                    throw new ErrorException('Ошибка при открытии файла архива');
                }
            }
            // если всё удачно- записываю в файл информации об обновлении время обновления
            file_put_contents($this->updateRoot . '\site_version.txt', $info[0] . "\r\n" . $info[1] . "\r\n");
	        if (!empty($destination)) {
		        $this->rmRec($destination);
	        }
            $session = Yii::$app->session;
            $session->setFlash('success', 'Обновления установлены. Вы используете последнюю версию ПО.');
            return ['status' => 1, 'message' => 'Обновления установлены. Вы используете последнюю версию ПО.'];
        }
        throw new ErrorException('Не удалось загрузить обновления');
    }

    public function checkUpdate():array
    {
        // проверю версию ПО сайта

        $file = fopen($this->updateRoot . '/site_version.txt', 'rb');
	    fgets($file);
        $update_time = fgets($file);
        fclose($file);
        // подключусь к диску, получу список обновлений, находящихся в папке
        $updates = $this->drive->checkUpdates();
        $counter = 0;
        // теперь определю, какие обновления не установлены
        if (!empty($updates)) {
            foreach ($updates as $update) {
                $date = null;
                preg_match('/^update\_\d{1,11}\-(\d{1,11})\.info?/', $update, $date);
                if (!empty($date[1]) && $update_time < $date[1]) {
                    // посчитаю обновление
                    $counter++;
                    $this->updates[] = $update;
                }
            }
        }
        if ($counter > 0){
            // загружу файлы с информацией об обновлениях
            $this->rmRec($this->updateRoot . '/downloaded_updates');
	        if (!mkdir($concurrentDirectory = $this->updateRoot . '/downloaded_updates', 0777, true) && !is_dir($concurrentDirectory)) {
		        throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
	        }
            foreach ($this->updates as $update) {
                // скачаю обновления
                $path = "Updates/$update";
                $destination = $this->updateRoot . '/downloaded_updates/';
                $name = $update;
                if (!$this->drive->downloadFile($path, $destination, $name)) {
                    return ['status' => 0, 'message' => 'Ошибка при загрузке обновления!'];
                }
            }
            // теперь соберу информацию об обновлениях
            $objects = array_slice(scandir($this->updateRoot . '/downloaded_updates', SCANDIR_SORT_NONE), 2);
            $information = [];
            $i = 0;
            foreach ($objects as $object) {
                if(is_file($this->updateRoot . '/downloaded_updates/' . $object)){
                    $file = fopen($this->updateRoot . '/downloaded_updates/' . $object, 'rb');
                    $information[$i]['update_version'] = fgets($file);
                    $information[$i]['update_time'] = fgets($file);
                    $information[$i]['description'] = stream_get_contents($file);
                    fclose($file);
                    $i++;
                }
            }
            $this->rmRec($this->updateRoot . '/downloaded_updates');
            return ['status' => 1, 'message' => "Обновлений найдено: $counter", 'information' => $information];
        }
        return ['status' => 0, 'message' => 'Обновлений не найдено, вы используете последнюю версию.'];
    }

    public function createUpdate():array
    {
        $time = time();
        // получаю информацию о времени предыдущего обновления
        if (is_file($this->updateRoot . '/site_version.txt')) {
            $file = fopen($this->updateRoot . '/site_version.txt', 'rb');
	        fgets($file);
            $update_time = fgets($file);
            if ($update_time) {
	            $this->lastUpdateTime = (int)$update_time;
            }
            else {
	            $this->lastUpdateTime = 0;
            }
            fclose($file);

        } else {
            $this->lastUpdateTime = 0;
        }
        // если есть предыдущее обновление - сотру его
        $this->rmRec($this->updateRoot . '/update');
        // поехали, получу структуру папок сайта
        foreach (UpdateSite::$scanningDirectories as $folder) {
            $this->recursiveFolderScan('/' . $folder);
        }
        $archiveName = $this->updateRoot . "/update-zip/update_{$this->lastUpdateTime}-{$this->currentTime}.zip";
        if(is_dir($this->updateRoot . '/update')){
            $za = new ZipArchive;
            $za->open($archiveName, ZipArchive::CREATE);
            // создаю файл со сведениями об обновлении
            file_put_contents($this->updateRoot . '/update_info.txt', $this->version . "\r\n" . $time . "\r\n" . $this->description . "\r\n");
             $za->addFile($this->updateRoot . '/update_info.txt', 'update_info.txt');
            // ищу папки в директории Update, каждую добавляю в архив
            $objects = array_slice(scandir($this->updateRoot . '/update', SCANDIR_SORT_NONE), 2);
            foreach ($objects as $object) {
                if (is_dir($this->updateRoot . "/update/$object")) {
	                $this->recursiveToZip($object, $za);
                }
            }
            $za->close();
            $this->rmRec($this->updateRoot . '/update');
        }
        //
        // Если файл обновления создан- загружу его в облако
        if (is_file($archiveName)) {
            $result = $this->drive->uploadFile("update_{$this->lastUpdateTime}-{$this->currentTime}.zip", $archiveName);
            $this->drive->uploadFile("update_{$this->lastUpdateTime}-{$this->currentTime}.info", $this->updateRoot . '/update_info.txt');
            unlink($this->updateRoot . '/update_info.txt');
            unlink($archiveName);
            if ($result === true) {
                // если всё удачно- записываю в файл информации об обновлении время обновления
                file_put_contents($this->updateRoot . '\site_version.txt', $this->version . "\r\n" . $time . "\r\n");
                $session = Yii::$app->session;
                $session->setFlash('success', 'Обновление создано и выгружено в облако.');
                return ['status' => 1, 'message' => 'Обновление создано и выгружено в облако'];
            }
                return ['status' => 0, 'message' => 'Ошибка выгрузки в облако'];
        }
	        return ['status' => 0, 'message' => 'Файлы не изменялись, обновление не требуется.'];
    }


    /**
     * @param string $folder
     * @param ZipArchive $zip
     */
    private function recursiveToZip($folder, $zip)
    {
        if (is_dir($this->updateRoot . "/update/$folder")) {
            $objects = array_slice(scandir($this->updateRoot . "/update/$folder", SCANDIR_SORT_NONE), 2);
            foreach ($objects as $object) {
                if (is_file($this->updateRoot . "/update/$folder/$object")) {
                    $zip->addFile($this->updateRoot . "/update/$folder/$object", $folder . "/$object");
                }
                if (is_dir($this->updateRoot . "/update/$folder/$object")) {
                    // сканирую папку рекурсивно
                    $this->recursiveToZip("$folder/$object", $zip);
                }
            }
        }
    }

    private function recursiveFolderScan($folder)
    {
        if (is_dir($this->root . $folder)) {
            $objects = array_slice(scandir($this->root . $folder, SCANDIR_SORT_NONE), 2);
            foreach ($objects as $object) {
                if (is_file($this->root . $folder . "/$object") && filemtime($this->root . $folder . "/$object") > $this->lastUpdateTime) {
                    // проверю наличие папки в update директории
                    if (!is_dir($this->updateRoot . '/update' . $folder) && !mkdir($concurrentDirectory = $this->updateRoot . '/update' . $folder, 0777, true) && !is_dir($concurrentDirectory)) {
	                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                    }
                    // копирую его в папку Update
                    copy($this->root . $folder . "/$object", $this->updateRoot . '/update' . $folder . "/$object");
                }
                if (is_dir($this->root . $folder . "/$object")) {
                    // сканирую папку рекурсивно
                    $this->recursiveFolderScan($folder . "/$object");
                }
            }
        }
    }

    private function rmRec($path)
    {
        if (is_file($path)) {
	        return unlink($path);
        }
        if (is_dir($path)) {
            foreach (scandir($path, SCANDIR_SORT_NONE) as $p) {
	            if (($p !== '.') && ($p !== '..')) {
		            $this->rmRec($path . DIRECTORY_SEPARATOR . $p);
	            }
            }
            return rmdir($path);
        }
        return false;
    }
}