<?php
namespace Controllers;

use Models\UserProgressModel;
use Services\Bot;
use Services\ButtonHandlerService;
use Services\KeyboardService;
use Services\MessageService;

class BotController
{
    private $user;
    private $bot;
    private $handler;
    private $message;
    private $keyboard;

    public function __construct($pdo)
    {
        $this->user = new UserProgressModel($pdo);
        $this->bot = new Bot($_ENV['BOT_TOKEN_PROFMET']);
        $this->message = new MessageService($this->bot);
        $this->keyboard = new KeyboardService();
        $this->handler = new ButtonHandlerService(
            $this->user,
            $this->keyboard,
            $this->message
        );
    }

    public function handle()
    {
        $update = json_decode(file_get_contents('php://input'), true);
        file_put_contents(__DIR__ . '/infoMessage.txt', print_r($update, true));

        if (!isset($update['message'])) return;

        $idTelegram = $update['message']['chat']['id'];
        $text       = $update['message']['text'] ?? '';

        // User check
        $user = $this->user->UserCheck($idTelegram);
        
        $fullData = [
            'idTelegram'    => $idTelegram,
            'text'          => $text,
            'userName'      => $user['name'],
            'userLevel'     => $user['levelId'],
            'history'       => $user['history']
            ];
        
        //UndefinedUser
        if (!$user) {
            $this->user->addUser($idTelegram, $update['message']['chat']['first_name']);
            $this->message->send($idTelegram,
            "🏠 Main menu: choose an option 👇",
            $this->keyboard->mainMenu());
            $data = [
                'position'=>'mainMenu',
                'lesson'=>'1',
                'vocabulary'=>'1',
                'phrase'=>'1'];
            $this->user->addHistory($idTelegram, $data);
            return;
        }
        
        if ($user['history'] && $this->handler->handle($fullData)) {
            return;
        }
    }
}