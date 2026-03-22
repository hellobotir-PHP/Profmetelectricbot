<?php
namespace Services;

class KeyboardService
{
    public function mainMenu()
    {
        return json_encode([
            'keyboard' => [
                ['📘 Lessons', 'ℹ️ About'],
                ['📝 Vocabulary', '💬 Phrases']
            ],
            'resize_keyboard' => true
        ]);
    }
    
    public function lesson($data)
    {
        return json_encode([
            'keyboard' => [
                ['⬅️ Previous',"Lesson-". $data, 'Next ➡️'],
                ['🔃 Refresh', '⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }
    
    public function vocabulary($data)
    {
        return json_encode([
            'keyboard' => [
                ['⬅️ Previous',"Vocabulary-". $data, 'Next ➡️'],
                ['🔃 Refresh', '⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }
    
    public function phrase($data)
    {
        return json_encode([
            'keyboard' => [
                ['⬅️ Previous',"Phrases-". $data, 'Next ➡️'],
                ['🔃 Refresh', '⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }
    
    public function about()
    {
        return json_encode([
            'keyboard' => [
                ['✏️ Edit name', '⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }

    public function editName()
    {
        return json_encode([
            'keyboard' => [
                ['⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }
    
    public function remove()
    {
        return json_encode([
            'remove_keyboard' => true
        ]);
    }
    
    public function back()
    {
        return json_encode([
            'keyboard' => [
                ['⬅️ Back']
            ],
            'resize_keyboard' => true
        ]);
    }
}
