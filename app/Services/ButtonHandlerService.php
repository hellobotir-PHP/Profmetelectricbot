<?php
namespace Services;

class ButtonHandlerService
{
    private $message, $keyboard, $user;
    
    private $levels = [
        '1' => '*A1 Beginner*',
        '2' => '*A2 Elementary*',
        '3' => '*B1 Intermediate*',
        '4' => '*B2 Upper-Intermediate*',
        '5' => '*C1 Advanced*',
        '6' => '*C2 Proficiency*'
        ];
    
    // private $tables = [
    //     'lesson' => 'mainEnglishLessons',
    //     'vocabulary' => 'mainEnglishVocabulary',
    //     'phrase' => 'mainEnglishPhrases'
    //     ];

    public function __construct($user, $keyboard, $message)
    {
        $this->user = $user;
        $this->keyboard = $keyboard;
        $this->message = $message;
    }

    public function handle($fullData)
    {
        $routes = [
            '🏠 Main menu'              => 'mainMenu',
            '📘 Lessons'                => 'lesson',
            'ℹ️ About'                  => 'about',
            '✏️ Edit name'              => 'editName',
            '📝 Vocabulary'             => 'vocabulary',
            '💬 Phrases'                => 'phrase',
            'Next ➡️'                   =>'next',
            '⬅️ Previous'               =>'previous',
            '🔃 Refresh'                =>'refresh',
            '⬅️ Back'                   => 'back'
        ];
        
        $fullData['history'] = json_decode($fullData['history'], true);
        
        if($fullData['history']['position']=="editName")
        {
            $this->user->updateUserInfo(
                $fullData['idTelegram'],
                ['name' => $fullData['text'],
                'levelId' => $fullData['userLevel']]);
                
            return $this->back($fullData);
        }
        
        if(!isset($routes[$fullData['text']]))
        {
            $method = $fullData['history']['position'];
            $fullData['method'] = $method;
            $fullData['text'] = (in_array($method, ['vocabulary', 'phrase']) && is_numeric($fullData['text'])) ? $fullData['text'] : "🤦";
            
            return $this->$method($fullData);
        }
        
        $method = $routes[$fullData['text']];
        $fullData['method'] = $method;
        return $this->$method($fullData);
    }
    
    //Main menu
    private function mainMenu($fullData)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            "mainMenu");
            
        $this->message->send(
            $fullData['idTelegram'],
            "🏠 Main menu: choose an option 👇",
            $this->keyboard->mainMenu());
    }
    
    //Lesson
    private function lesson($fullData)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            "lesson");
            
        $word = $this->user->ReadBase(
            $this->tables['lesson'],
            $fullData['history']['lesson']);
            
        $index = $fullData['history'][$fullData['method']];
        
        if(isset($fullData['com']))
        {
            $word = $this->user->ReadBase($this->tables['lesson'], $fullData['index']);
            $index = $fullData['index'];
        }
            
        $msg = $this->levels[$word['levelId']].
        "\n".$word['lesson'];
        
        $this->message->send(
            $fullData['idTelegram'],
            $msg,
            $this->keyboard->lesson($index));
            
        $folder = ['name'=>'lesson', 'id'=>$index];
        $this->message->voice($fullData['idTelegram'], $folder);
        
        if($fullData['userLevel'] != $word['levelId'])
        {
            $this->user->updateUserInfo(
                $fullData['idTelegram'],
                ['name'=>$fullData['userName'], 'levelId'=>$word['levelId']]);
        }
    }
    
    //About
    private function about($fullData)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            "about");
            
        $msg = "Your name: " . $fullData['userName'] . "\nYour level: " . $this->levels[$fullData['userLevel']];
        
        $this->message->send(
            $fullData['idTelegram'],
            $msg,
            $this->keyboard->about());
    }
    
    //Edit name
    private function editName($fullData)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            "editName");
            
        $this->message->send(
            $fullData['idTelegram'],
            "Write your name",
            $this->keyboard->remove());
    }
    
    //Content handler
    private function contentHandler($fullData, $table)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            $fullData['method']);
            
        if(filter_var($fullData['text'], FILTER_VALIDATE_INT) !== false) $fullData['history'][$fullData['method']] = $fullData['text'];
        
        $word = $this->user->ReadBase($table, $fullData['history'][$fullData['method']]);
        
        $index = $fullData['history'][$fullData['method']];
        
        if(isset($fullData['com']))
        {
            $word = $this->user->ReadBase($table, $fullData['index']);
            $index = $fullData['index'];
        }
        
        $msg = 
        "*En:* " . $word['enWord'] . " — *Spelling:* " . $word['enSpelling'] .
        "\n*Uz:* " . "||".$word['uzWord']. "||".
        "\n*Ru:* " . "||".$word['ruWord']. "||".
        "\n*Tr:* " . "||".$word['trWord']. "||";
        
        $this->message->send(
            $fullData['idTelegram'],
            $msg,
            $this->keyboard->{$fullData['method']}($index));
        
        $this->message->voice(
            $fullData['idTelegram'],
            ['name' => $fullData['method'],
            'id' => $index]);
    }
    
    //Vocabulary
    private function vocabulary($fullData)
    {
        $this->contentHandler($fullData, $this->tables['vocabulary']);
    }
    
    //Phrases
    private function phrase($fullData)
    {
        $this->contentHandler($fullData, $this->tables['phrase']);
    }
    
    //Next
    private function next($fullData)
    {
        $history = $fullData['history'];
        $position = $history['position'];
        
        $count = $this->user->ReadCountBase($this->tables[$position]);
        $next = min($history[$position] + 1, $count);
        
        $this->user->updatePagination(
            $fullData['idTelegram'],
            $history,
            $position,
            $next
            );
        $fullData['history'][$position] = $next;
        $fullData['method'] = $position;
        $this->$position($fullData);
    }
    
    //Previous
    private function previous($fullData)
    {
        $history = $fullData['history'];
        $position = $history['position'];
        
        $previous = max($history[$position] - 1, 1);
        
        $this->user->updatePagination(
            $fullData['idTelegram'],
            $history,
            $position,
            $previous
            );
        $fullData['history'][$position] = $previous;
        $fullData['method'] = $position;
        $this->$position($fullData);
    }
    
    //Refresh
    private function refresh($fullData)
    {
        $history = $fullData['history'];
        $position = $history['position'];
        
        $index = $history[$position] ?? 1;
        $index = max(1, $index);
        $index = random_int(1, $index);
        $index = (int) $index;
        $fullData ['com'] = "refresh";
        $fullData ['index'] = $index;
        $fullData['method'] = $position;
        $this->$position($fullData);
    }
    
    //Back
    private function back($fullData)
    {
        $this->user->updatePosition(
            $fullData['idTelegram'],
            $fullData['history'],
            'mainMenu');
        
        $this->message->send(
                $fullData['idTelegram'],
                "🏠 Main menu: choose an option 👇",
                $this->keyboard->mainMenu());
    }
}