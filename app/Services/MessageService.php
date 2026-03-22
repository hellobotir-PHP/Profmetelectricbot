<?php
namespace Services;

class MessageService
{
    private $bot;
    private static $drive = null;

    public function __construct($bot)
    {
        $this->bot = $bot;
    }

    public function send($idTelegram, $text, $keyboard = null)
    {
        $text = preg_split('/(\|\|.*?\|\|)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($text as &$part) {
            if (preg_match('/^\|\|.*\|\|$/s', $part)) {
                continue;
            }
            $escape_chars = ['\\', '_', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            foreach ($escape_chars as $char) {
                $part = str_replace($char, '\\'.$char, $part);
            }
        }
        
        $text = implode('', $text);
        $data = [
            'chat_id' => $idTelegram,
            'text' => $text,
            'parse_mode' => 'MarkdownV2'
        ];
        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }
        
        return $this->bot->request('sendMessage', $data);
    }
    
    public function voice($idTelegram, $folder)
    {
        $drive  = $this->getDrive();
        $rootId = $_ENV['DRIVE_ROOT_ID'] ?? getenv('DRIVE_ROOT_ID') ?: '';

        if ($rootId === '') {
            return false;
        }

        $type = $folder['name'] ?? '';
        $num  = (string)($folder['id'] ?? '');
        if ($type === '' || $num === '') {
            return false;
        }

        // Variant B: ROOT ichidan bevosita type papkani qidiramiz (upload yo'q)
        $typeId = $this->findFolderIdByName($drive, $type, $rootId);
        if (!$typeId) {
            return false;
        }

        $numId = $this->findFolderIdByName($drive, $num, $typeId);
        if (!$numId) {
            return false;
        }

        $files = $this->listOggFilesInFolder($drive, $numId);
        if (empty($files)) {
            return false;
        }

        usort($files, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        foreach ($files as $f) {
            $tmp = sys_get_temp_dir() . '/' . uniqid('drv_', true) . '_' . $this->safeName($f['name']);

            $this->downloadDriveFileToPath($drive, $f['id'], $tmp);

            $voiceFile = new \CURLFile($tmp, 'audio/ogg', $f['name']);
            $data = [
                'chat_id' => $idTelegram,
                'voice'   => $voiceFile,
            ];
            $this->bot->request('sendVoice', $data);

            @unlink($tmp);
            usleep(100000);
        }

        return true;
    }

    private function getDrive()
    {
        if (self::$drive !== null) {
            return self::$drive;
        }

        $saJson = $_ENV['DRIVE_SA_JSON'] ?? getenv('DRIVE_SA_JSON') ?: '';
        if ($saJson === '' || !is_file($saJson)) {
            return null; // yoki throw qilsangiz ham bo'ladi
        }

        $client = new \Google_Client();
        $client->setAuthConfig($saJson);
        $client->setScopes([\Google_Service_Drive::DRIVE_READONLY]);

        self::$drive = new \Google_Service_Drive($client);
        return self::$drive;
    }


    private function findFolderIdByName($drive, string $name, string $parentId): ?string
    {
        $name = addslashes($name);
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$name}' and trashed=false and '{$parentId}' in parents";
        $res = $drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 10,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);
        $files = $res->getFiles();
        return $files ? $files[0]->getId() : null;
    }

    private function listOggFilesInFolder($drive, string $folderId): array
    {
        $q = "'{$folderId}' in parents and trashed=false and (mimeType='audio/ogg' or name contains '.ogg')";
        $res = $drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id,name,mimeType)',
            'pageSize' => 100,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $out = [];
        foreach ($res->getFiles() as $f) {
            $out[] = ['id' => $f->getId(), 'name' => $f->getName()];
        }
        return $out;
    }

    private function downloadDriveFileToPath($drive, string $fileId, string $savePath): void
    {
        $response = $drive->files->get($fileId, ['alt' => 'media']);
        $body = $response->getBody();

        $fh = fopen($savePath, 'wb');
        if (!$fh) {
            throw new \RuntimeException("Temp file ochilmadi: {$savePath}");
        }

        while (!$body->eof()) {
            fwrite($fh, $body->read(1024 * 1024));
        }
        fclose($fh);
    }

    private function safeName(string $name): string
    {
        return preg_replace('~[^\w\.\-]+~u', '_', $name);
    }
}