<?php
namespace Models;

use PDO;

class UserProgressModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // User check
    public function UserCheck($idTelegram)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ProfmetUsers WHERE idTelegram = ?"
        );

        $stmt->execute([$idTelegram]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Add user
    public function addUser($idTelegram, $data)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ProfmetUsers (id, idTelegram, name, levelId, history)
            VALUES (NULL, ?, ?, 1, NULL)");

        return $stmt->execute([$idTelegram, $data]);
    }

    // History write
    public function addHistory($idTelegram, $data)
    {
        $data = json_encode($data);
        
        $stmt = $this->pdo->prepare(
            "UPDATE ProfmetUsers SET history = ? WHERE idTelegram = ?"
        );

        return $stmt->execute([$data, $idTelegram]);
    }
    
    //
    public function updateUserInfo($idTelegram, $data)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ProfmetUsers SET name = ?, levelId = ? WHERE idTelegram = ?"
        );

        return $stmt->execute([$data['name'], $data['levelId'], $idTelegram]);
    }
    
    // Position update
    public function updatePosition($idTelegram, $data, $position)
    {
        $data['position'] = $position;
        $data = json_encode($data);
        $stmt = $this->pdo->prepare(
            "UPDATE ProfmetUsers SET history = ? WHERE idTelegram = ?"
        );

        return $stmt->execute([$data, $idTelegram]);
    }
    
    // ReadBase
    public function ReadBase($table, $id)
    {
        // $allowedTables = ['mainEnglishPhrases', 'mainEnglishVocabulary', 'mainEnglishLessons'];
        if (!in_array($table, $allowedTables)) {
            return false;
        }
        
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    //ReadCountBase
    public function ReadCountBase($table)
    {
        // $allowedTables = ['mainEnglishPhrases', 'mainEnglishVocabulary', 'mainEnglishLessons'];
        if (!in_array($table, $allowedTables)) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    // Next update
    public function updatePagination($idTelegram, $history, $position, $next)
    {
        $history[$position] = $next;
        $data = json_encode($history);
        $stmt = $this->pdo->prepare(
            "UPDATE ProfmetUsers SET history = ? WHERE idTelegram = ?"
        );
        
        return $stmt->execute([$data, $idTelegram]);
    }
}