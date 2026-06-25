# OGame Testing Infrastructure

## Overview
OGame uses PHPUnit for unit testing and PHPStan for static analysis. The testing infrastructure is designed to test the game engine without requiring a full game installation.

## Testing Tools

### PHPUnit
- Version: 10.x
- Configuration: `phpunit.xml`
- Bootstrap: `vendor/autoload.php`
- Test Directory: `testing/`

### PHPStan
- Version: 2.x
- Configuration: `phpstan.neon`
- Level: 8 (strict)
- Paths: `game/`, `wwwroot/`

## Test Configuration

### phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    
    <testsuites>
        <testsuite name="OGame Open Source Test Suite">
            <directory>testing</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">testing</directory>
        </whitelist>
    </filter>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
    
</phpunit>
```

### phpstan.neon
```yaml
parameters:
  level: 8
  paths:
    - game
    - wwwroot
  excludePaths:
    - BattleEngine/*
    - download/*
    - wiki/*
    - game/css/*
    - game/js/*
    - game/img/*
    - game/loca/*
    - game/temp/*
    - wwwroot/evolution/*
    - wwwroot/css/*
    - wwwroot/js/*
    - wwwroot/img/*
    - game/core/bbcode.php
  ignoreErrors:
    - identifier: missingType.iterableValue
  reportUnmatchedIgnoredErrors: false
```

## Test Files

### mock_functions.php
**Location**: `testing/mock_functions.php`

This file provides mock implementations of core functions for testing.

#### Mock Functions

##### dbquery
Mock database query function.
```php
function dbquery(string $query) : mixed {
    global $dbQueryCalls, $mockDbResult;
    $dbQueryCalls[] = $query;
    return $mockDbResult;
}
```

##### dbarray
Mock database array fetch function.
```php
function dbarray(mixed $result) : mixed {
    if ($result && is_object($result) && isset($result->data)) {
        if (!$result->fetched) {
            $result->fetched = true;
            return $result->data;
        }
    }
    return false;
}
```

##### LoadUser
Mock user loading function.
```php
function LoadUser(int $player_id) : array {
    global $mockUserData;
    return $mockUserData;
}
```

##### loca_add
Mock localization add function.
```php
function loca_add(string $module, string $lang) : void {
    global $locaAddCalls;
    $locaAddCalls[] = $module;
}
```

##### loca
Mock localization function.
```php
function loca(string $key) : string {
    $translations = [
        'NOTE_NO_SUBJ' => 'No subject',
        'NOTE_NO_TEXT' => 'No text'
    ];
    return $translations[$key] ?? $key;
}
```

##### AddDBRow
Mock database row insertion function.
```php
function AddDBRow(array $data, string $table) : int {
    global $addDBRowCalls, $lastAddDBRowData;
    $addDBRowCalls++;
    $lastAddDBRowData = $data;
    return $addDBRowCalls;
}
```

#### Global Variables for Testing

| Variable | Type | Description |
|----------|------|-------------|
| `$mockDbResult` | mixed | Mock database result |
| `$mockUserData` | array | Mock user data |
| `$dbQueryCalls` | array | Array of query calls |
| `$addDBRowCalls` | int | Count of AddDBRow calls |
| `$lastAddDBRowData` | array | Last AddDBRow data |
| `$locaAddCalls` | array | Array of loca_add calls |

## Sample Tests

### SimpleTest.php
**Location**: `testing/SimpleTest.php`

Basic test to verify test framework is working.

```php
<?php

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    protected function setUp(): void {
        // Setup before each test
    }

    protected function tearDown(): void {
        // Cleanup after each test
    }

    public function test1() {
        $this->assertEquals(1, 1);
    }
}
```

### HomepageTest.php
**Location**: `testing/HomepageTest.php`

Tests homepage loading and basic functionality.

### NotesTest.php
**Location**: `testing/NotesTest.php`

Tests notes management functionality.

## Running Tests

### PHPUnit
```bash
# Run all tests
phpunit

# Run specific test file
phpunit testing/SimpleTest.php

# Run with coverage
phpunit --coverage-html=coverage/

# Run with verbose output
phpunit --verbose
```

### PHPStan
```bash
# Run static analysis
vendor/bin/phpstan analyse

# Run with specific level
vendor/bin/phpstan analyse --level=5

# Generate report
vendor/bin/phpstan analyse --error-format=table
```

## Test Best Practices

### 1. Test Isolation
Each test should be independent and not affect other tests.

```php
public function testExample() {
    // Setup
    $this->setUp();
    
    // Execute
    $result = $this->subject->method();
    
    // Assert
    $this->assertEquals($expected, $result);
    
    // Cleanup
    $this->tearDown();
}
```

### 2. Mock External Dependencies
Use mocks for database, file system, and other external systems.

```php
public function testDatabaseQuery() {
    $mockDb = $this->createMock(Db::class);
    $mockDb->method('query')
           ->willReturn($mockResult);
    
    // Test with mock
}
```

### 3. Test Edge Cases
Test boundary conditions and error handling.

```php
public function testEmptyInput() {
    $this->expectException(InvalidArgumentException::class);
    $this->subject->method('');
}

public function testMaximumValue() {
    $result = $this->subject->method(PHP_INT_MAX);
    $this->assertNotNull($result);
}
```

### 4. Test Public API Only
Test only the public interface, not implementation details.

```php
// Good
$result = $object->publicMethod();

// Bad
$result = $object->privateMethod(); // Test implementation
```

## Testing Strategy

### Unit Tests
Test individual functions and classes in isolation.

**Focus Areas**:
- Database layer functions
- Calculation functions
- String manipulation
- Data validation

### Integration Tests
Test interactions between components.

**Focus Areas**:
- Page loading
- User authentication
- Fleet movement
- Battle simulation

### Acceptance Tests
Test complete user workflows.

**Focus Areas**:
- User registration
- Planet colonization
- Building construction
- Fleet combat

## Code Coverage

### Target Coverage
- Database layer: 90%
- Core classes: 80%
- Page classes: 70%
- Battle engine: 60%

### Coverage Report
```bash
phpunit --coverage-html=coverage/
```

## Continuous Integration

### CI Pipeline
1. Run PHPStan analysis
2. Run PHPUnit tests
3. Check code coverage
4. Generate documentation

### Example CI Script
```bash
#!/bin/bash

# Run PHPStan
vendor/bin/phpstan analyse --level=8

# Run tests
phpunit --coverage-text

# Check coverage
if [ $? -ne 0 ]; then
    echo "Tests failed"
    exit 1
fi

echo "All checks passed"
```

## Debugging Tests

### Enable Debug Mode
```php
define('DEBUG', true);
```

### Log Test Output
```php
file_put_contents('test_debug.log', print_r($data, true), FILE_APPEND);
```

### Xdebug Profiling
```bash
phpunit --xdebug-profile
```

## Future Enhancements

### Planned Tests
1. **Database Tests**
   - Connection tests
   - Query tests
   - Transaction tests

2. **Battle Tests**
   - Simple battle tests
   - Rapidfire tests
   - Large fleet tests

3. **UI Tests**
   - Page rendering tests
   - Form submission tests
   - AJAX response tests

4. **Performance Tests**
   - Query performance
   - Memory usage
   - Execution time

### Tooling Improvements
1. Mock database layer
2. Test data fixtures
3. Test environment setup
4. Performance benchmarks

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check database configuration
   - Verify database is running
   - Test connection manually

2. **Mock Not Working**
   - Check function name matches
   - Verify global variables
   - Check include paths

3. **Coverage Not Generating**
   - Install Xdebug
   - Check PHP configuration
   - Verify write permissions

### Debug Commands
```bash
# Run specific test with verbose output
phpunit --verbose testing/SimpleTest.php

# Run with debug mode
phpunit -d display_errors=1

# Generate coverage
phpunit --coverage-html=coverage/
```

### Log Files
- Test log: `test_debug.log`
- Coverage: `coverage/index.html`
- PHPStan report: `phpstan_report_latest.txt`
