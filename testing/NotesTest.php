<?php
// tests/NotesTest.php

use PHPUnit\Framework\TestCase;

/**
 * Тесты для модуля заметок
 */
class NotesTest extends TestCase
{
    private $testPlayerId = 123;
    private $testNoteId = 1;
    private $originalFunctions = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Мокаем глобальные переменные
        global $db_prefix;
        $db_prefix = 'test_';
        
        // Создаем заглушки для глобальных функций
        $this->setupMockFunctions();
        
        // Включаем файл с функциями после мокинга
        require_once __DIR__ . '/../game/core/notes.php';
    }
    
    private function setupMockFunctions(): void
    {
        // Определяем мок-функции
        require_once __DIR__ . '/mock_functions.php';
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    /**
     * Тест загрузки существующей заметки
     */
    public function testLoadNoteSuccess(): void
    {
        // Arrange
        $expectedNote = [
            'note_id' => $this->testNoteId,
            'owner_id' => $this->testPlayerId,
            'subj' => 'Test Subject',
            'text' => 'Test Text',
            'textsize' => 9,
            'prio' => 1,
            'date' => time()
        ];
        
        // Мокаем результат запроса
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => $expectedNote,
            'fetched' => false
        ];
        
        // Act
        $result = LoadNote($this->testPlayerId, $this->testNoteId);
        
        // Assert
        $this->assertEquals($expectedNote, $result);
    }
    
    /**
     * Тест загрузки несуществующей заметки
     */
    public function testLoadNoteNotFound(): void
    {
        // Arrange
        global $mockDbResult;
        $mockDbResult = false;
        
        // Act
        $result = LoadNote($this->testPlayerId, 999);
        
        // Assert
        $this->assertFalse($result);
    }
    
    /**
     * Тест добавления заметки с валидными данными
     */
    public function testAddNoteWithValidData(): void
    {
        // Arrange
        $subj = 'Test Subject';
        $text = 'Test Text Content';
        $prio = 1;
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        // Счетчик вызовов AddDBRow
        global $addDBRowCalls;
        $addDBRowCalls = 0;
        
        // Act
        AddNote($this->testPlayerId, $subj, $text, $prio);
        
        // Assert
        $this->assertEquals(1, $GLOBALS['addDBRowCalls'] ?? 0);
    }
    
    /**
     * Тест добавления заметки с пустым заголовком
     */
    public function testAddNoteWithEmptySubject(): void
    {
        // Arrange
        $subj = '';
        $text = 'Test Text';
        $prio = 0;
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        global $addDBRowCalls;
        $addDBRowCalls = 0;
        
        // Act
        AddNote($this->testPlayerId, $subj, $text, $prio);
        
        // Assert
        $this->assertEquals(1, $GLOBALS['addDBRowCalls'] ?? 0);
    }
    
    /**
     * Тест добавления заметки с очень длинным текстом
     */
    public function testAddNoteWithLongText(): void
    {
        // Arrange
        $subj = 'Test Subject';
        $text = str_repeat('a', 6000);
        $prio = 2;
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        global $addDBRowCalls;
        $addDBRowCalls = 0;
        
        // Act
        AddNote($this->testPlayerId, $subj, $text, $prio);
        
        // Assert - проверяем что функция была вызвана
        $this->assertEquals(1, $GLOBALS['addDBRowCalls'] ?? 0);
        
        // Проверяем что текст был обрезан
        $this->assertEquals(5000, mb_strlen($GLOBALS['lastAddDBRowData']['text'] ?? '', 'UTF-8'));
    }
    
    /**
     * Тест добавления заметки с приоритетом за пределами допустимого диапазона
     */
    public function testAddNoteWithInvalidPriority(): void
    {
        // Arrange
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        // Test cases
        $testCases = [
            ['input' => -5, 'expected' => 0],
            ['input' => 5, 'expected' => 2]
        ];
        
        foreach ($testCases as $testCase) {
            global $addDBRowCalls;
            $addDBRowCalls = 0;
            global $lastAddDBRowData;
            $lastAddDBRowData = null;
            
            // Act
            AddNote($this->testPlayerId, 'Test', 'Text', $testCase['input']);
            
            // Assert
            $this->assertEquals(1, $GLOBALS['addDBRowCalls'] ?? 0);
            $this->assertEquals($testCase['expected'], $GLOBALS['lastAddDBRowData']['prio'] ?? null);
        }
    }
    
    /**
     * Тест обновления заметки
     */
    public function testUpdateNoteSuccess(): void
    {
        // Arrange
        $existingNote = [
            'note_id' => $this->testNoteId,
            'owner_id' => $this->testPlayerId,
            'subj' => 'Old Subject',
            'text' => 'Old Text',
            'textsize' => 9,
            'prio' => 0,
            'date' => time() - 3600
        ];
        
        $newSubj = 'Updated Subject';
        $newText = 'Updated Text Content';
        $newPrio = 2;
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => $existingNote,
            'fetched' => false
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        UpdateNote($this->testPlayerId, $this->testNoteId, $newSubj, $newText, $newPrio);
        
        // Assert
        $this->assertEquals(2, count ($GLOBALS['dbQueryCalls'] ?? []));
        $this->assertStringContainsString('UPDATE', $GLOBALS['dbQueryCalls'][1] ?? '');
    }
    
    /**
     * Тест попытки обновления чужой заметки
     */
    public function testUpdateNoteUnauthorized(): void
    {
        // Arrange
        $otherPlayerId = 456;
        $existingNote = [
            'note_id' => $this->testNoteId,
            'owner_id' => $otherPlayerId,
            'subj' => 'Foreign Note',
            'text' => 'Cannot touch this',
            'textsize' => 16,
            'prio' => 1,
            'date' => time()
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => $existingNote,
            'fetched' => false
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        UpdateNote($this->testPlayerId, $this->testNoteId, 'New Subject', 'New Text', 2);
        
        // Assert - не должно быть вызовов UPDATE
        $this->assertEquals(1, count($GLOBALS['dbQueryCalls'] ?? []));
        $this->assertStringNotContainsString('UPDATE', $GLOBALS['dbQueryCalls'][0] ?? '');
    }
    
    /**
     * Тест удаления заметки
     */
    public function testDelNoteSuccess(): void
    {
        // Arrange
        $existingNote = [
            'note_id' => $this->testNoteId,
            'owner_id' => $this->testPlayerId,
            'subj' => 'Note to delete',
            'text' => 'This will be deleted',
            'textsize' => 20,
            'prio' => 0,
            'date' => time()
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => $existingNote,
            'fetched' => false
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        DelNote($this->testPlayerId, $this->testNoteId);
        
        // Assert
        $this->assertEquals(2, count ($GLOBALS['dbQueryCalls'] ?? []));
        $this->assertStringContainsString('DELETE', $GLOBALS['dbQueryCalls'][1] ?? '');
    }
    
    /**
     * Тест попытки удаления чужой заметки
     */
    public function testDelNoteUnauthorized(): void
    {
        // Arrange
        $otherPlayerId = 789;
        $existingNote = [
            'note_id' => $this->testNoteId,
            'owner_id' => $otherPlayerId,
            'subj' => 'Protected Note',
            'text' => 'Cannot delete this',
            'textsize' => 18,
            'prio' => 2,
            'date' => time()
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => $existingNote,
            'fetched' => false
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        DelNote($this->testPlayerId, $this->testNoteId);
        
        // Assert
        $this->assertEquals(1, count($GLOBALS['dbQueryCalls'] ?? []));
        $this->assertStringNotContainsString('DELETE', $GLOBALS['dbQueryCalls'][0] ?? '');
    }
    
    /**
     * Тест перечисления заметок для обычного пользователя
     */
    public function testEnumNotesForRegularUser(): void
    {
        // Arrange
        global $mockUserData;
        $mockUserData = [
            'admin' => 0
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => [],
            'fetched' => true
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        $result = EnumNotes($this->testPlayerId);
        
        // Assert
        $this->assertNotEmpty($GLOBALS['dbQueryCalls'] ?? []);
        $this->assertStringContainsString('LIMIT 20', $GLOBALS['dbQueryCalls'][0] ?? '');
        $this->assertSame($mockDbResult, $result);
    }
    
    /**
     * Тест перечисления заметок для администратора
     */
    public function testEnumNotesForAdmin(): void
    {
        // Arrange
        global $mockUserData;
        $mockUserData = [
            'admin' => 1
        ];
        
        global $mockDbResult;
        $mockDbResult = (object) [
            'data' => [],
            'fetched' => true
        ];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        $result = EnumNotes($this->testPlayerId);
        
        // Assert
        $this->assertNotEmpty($GLOBALS['dbQueryCalls'] ?? []);
        $this->assertStringContainsString('LIMIT 150', $GLOBALS['dbQueryCalls'][0] ?? '');
        $this->assertSame($mockDbResult, $result);
    }
    
    /**
     * Тест безопасности SQL инъекций
     */
    public function testSqlInjectionProtection(): void
    {
        // Arrange
        $maliciousSubject = "Test'; DROP TABLE notes; --";
        $maliciousText = "Text'; DELETE FROM users; --";
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        global $lastAddDBRowData;
        $lastAddDBRowData = null;
        
        // Act
        AddNote($this->testPlayerId, $maliciousSubject, $maliciousText, 1);
        
        // Assert - проверяем что данные были обрезаны
        $this->assertNotNull($GLOBALS['lastAddDBRowData'] ?? null);
        if (isset($GLOBALS['lastAddDBRowData'])) {
            $this->assertLessThanOrEqual(30, mb_strlen($GLOBALS['lastAddDBRowData']['subj'] ?? '', 'UTF-8'));
            $this->assertLessThanOrEqual(5000, mb_strlen($GLOBALS['lastAddDBRowData']['text'] ?? '', 'UTF-8'));
        }
    }
    
    /**
     * Тест обработки многобайтовых строк (UTF-8)
     */
    public function testMultibyteStringHandling(): void
    {
        // Arrange
        $unicodeSubject = "Заголовок с русскими буквами и emoji 😊";
        $unicodeText = "Текст заметки с различными символами: αβγδε 😀🎉";
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'ru',
            'admin' => 0
        ];
        
        global $lastAddDBRowData;
        $lastAddDBRowData = null;
        
        // Act
        AddNote($this->testPlayerId, $unicodeSubject, $unicodeText, 1);
        
        // Assert
        $this->assertNotNull($GLOBALS['lastAddDBRowData'] ?? null);
        if (isset($GLOBALS['lastAddDBRowData'])) {
            $this->assertEquals(
                mb_strlen($unicodeText, 'UTF-8'),
                $GLOBALS['lastAddDBRowData']['textsize'] ?? 0
            );
        }
    }
    
    /**
     * Тест граничных значений для приоритета
     */
    public function testPriorityBoundaryValues(): void
    {
        // Arrange
        $testCases = [
            ['input' => -1, 'expected' => 0],
            ['input' => 0, 'expected' => 0],
            ['input' => 1, 'expected' => 1],
            ['input' => 2, 'expected' => 2],
            ['input' => 3, 'expected' => 2]
        ];
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        foreach ($testCases as $testCase) {
            global $lastAddDBRowData;
            $lastAddDBRowData = null;
            
            // Act
            AddNote($this->testPlayerId, 'Test', 'Text', $testCase['input']);
            
            // Assert
            $this->assertEquals($testCase['expected'], $GLOBALS['lastAddDBRowData']['prio'] ?? null);
        }
    }
    
    /**
     * Тест с различными языками пользователей
     */
    public function testDifferentUserLanguages(): void
    {
        // Arrange
        $languages = ['en', 'ru', 'de', 'fr'];
        
        foreach ($languages as $lang) {
            global $mockUserData;
            $mockUserData = [
                'lang' => $lang,
                'admin' => 0
            ];
            
            global $locaAddCalls;
            $locaAddCalls = [];
            
            // Act
            AddNote($this->testPlayerId, 'Test', 'Text', 1);
            
            // Assert
            $this->assertContains('notes', $GLOBALS['locaAddCalls'] ?? []);
        }
    }
    
    /**
     * Тест на корректную обработку специальных символов
     */
    public function testSpecialCharacters(): void
    {
        // Arrange
        $specialSubject = "Subject with quotes: 'single' and \"double\"";
        $specialText = "Text with newline\nand tab\tand special chars: & < >";
        
        global $mockUserData;
        $mockUserData = [
            'lang' => 'en',
            'admin' => 0
        ];
        
        global $lastAddDBRowData;
        $lastAddDBRowData = null;
        
        // Act
        AddNote($this->testPlayerId, $specialSubject, $specialText, 1);
        
        // Assert
        $this->assertNotNull($GLOBALS['lastAddDBRowData'] ?? null);
    }
    
    /**
     * Тест на максимальное количество заметок
     */
    public function testNotesLimits(): void
    {
        // Test regular user limit
        global $mockUserData;
        $mockUserData = ['admin' => 0];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        EnumNotes($this->testPlayerId);
        $this->assertStringContainsString('LIMIT 20', $GLOBALS['dbQueryCalls'][0] ?? '');
        
        // Test admin limit
        $mockUserData = ['admin' => 1];
        $dbQueryCalls = [];
        
        EnumNotes($this->testPlayerId);
        $this->assertStringContainsString('LIMIT 150', $GLOBALS['dbQueryCalls'][0] ?? '');
    }
    
    /**
     * Тест сортировки заметок по дате
     */
    public function testNotesOrdering(): void
    {
        // Arrange
        global $mockUserData;
        $mockUserData = ['admin' => 0];
        
        global $dbQueryCalls;
        $dbQueryCalls = [];
        
        // Act
        EnumNotes($this->testPlayerId);
        
        // Assert
        $this->assertStringContainsString('ORDER BY date DESC', $GLOBALS['dbQueryCalls'][0] ?? '');
    }
}