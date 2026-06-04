<?php

/**
 * GradeTest.php
 *
 * PHPUnit test suite for the Grade Management component.
 * Tests the Grade middle layer class and boundary validation
 * for the gradeName and description fields of tblGrade.
 *
 * How to run in VS Code:
 * 1. Install PHPUnit:  composer require --dev phpunit/phpunit
 * 2. Run all tests:    ./vendor/bin/phpunit GradeTest.php --testdox
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

use PHPUnit\Framework\TestCase;

/**
 * Standalone validation helper that mirrors the logic in add.php / edit.php.
 * Extracted so it can be unit-tested without a live database connection.
 */
class GradeValidator
{
    // ── Field constraints (from tblGrade in edusync.sql) ──────────────────
    const GRADE_NAME_MAX   = 50;   // VARCHAR(50) NOT NULL UNIQUE
    const DESCRIPTION_MAX  = 255;  // VARCHAR(255) DEFAULT NULL

    /**
     * Validate a grade name.
     *
     * Rules (mirroring add.php / edit.php):
     *   1. trim() is applied first.
     *   2. Empty string is not allowed (NOT NULL).
     *   3. Maximum length is 50 characters (VARCHAR(50)).
     *
     * @param  string $gradeName Raw input from form POST.
     * @return string|null  null if valid, error message if invalid.
     */
    public static function validateGradeName(string $gradeName): ?string
    {
        $trimmed = trim($gradeName);

        if ($trimmed === '') {
            return 'Grade name is required.';
        }

        if (strlen($trimmed) > self::GRADE_NAME_MAX) {
            return 'Grade name must not exceed ' . self::GRADE_NAME_MAX . ' characters.';
        }

        return null; // valid
    }

    /**
     * Validate an optional description.
     *
     * Rules (mirroring add.php / edit.php):
     *   1. trim() is applied first.
     *   2. Empty string / NULL is allowed (DEFAULT NULL).
     *   3. Maximum length is 255 characters (VARCHAR(255)).
     *
     * @param  string $description Raw input from form POST.
     * @return string|null  null if valid, error message if invalid.
     */
    public static function validateDescription(string $description): ?string
    {
        $trimmed = trim($description);

        // Empty is allowed for optional field
        if ($trimmed === '') {
            return null;
        }

        if (strlen($trimmed) > self::DESCRIPTION_MAX) {
            return 'Description must not exceed ' . self::DESCRIPTION_MAX . ' characters.';
        }

        return null; // valid
    }

    /**
     * Convert an optional description to its stored value.
     * Mirrors: $description ?: null  in add.php.
     *
     * @param  string $description Raw trimmed input.
     * @return string|null  null if empty, trimmed string if not.
     */
    public static function prepareDescription(string $description): ?string
    {
        $trimmed = trim($description);
        return $trimmed !== '' ? $trimmed : null;
    }
}


// ══════════════════════════════════════════════════════════════════════════════
/**
 * Test suite for GradeValidator::validateGradeName()
 *
 * Boundary values based on VARCHAR(50) NOT NULL UNIQUE:
 *   Min boundary = 1 character
 *   Max boundary = 50 characters
 */
// ══════════════════════════════════════════════════════════════════════════════
class GradeNameValidationTest extends TestCase
{
    // ── Extreme Min / Below Min ────────────────────────────────────────────

    /**
     * @test
     * Extreme Min: empty string (0 chars) must fail validation.
     */
    public function testGradeName_ExtremeMin_EmptyString_Fails(): void
    {
        $result = GradeValidator::validateGradeName('');
        $this->assertSame('Grade name is required.', $result,
            'Empty string should fail: grade name is required.');
    }

    /**
     * @test
     * Min-1: whitespace-only input trims to empty — must fail.
     */
    public function testGradeName_MinMinus1_WhitespaceOnly_Fails(): void
    {
        $result = GradeValidator::validateGradeName('   ');
        $this->assertSame('Grade name is required.', $result,
            'Whitespace-only input should trim to empty and fail.');
    }

    // ── Min Boundary ───────────────────────────────────────────────────────

    /**
     * @test
     * Min (Boundary): 1-character grade name must pass.
     */
    public function testGradeName_MinBoundary_OneChar_Passes(): void
    {
        $result = GradeValidator::validateGradeName('A');
        $this->assertNull($result,
            '1-character grade name should pass validation.');
    }

    /**
     * @test
     * Min+1: 2-character grade name must pass.
     */
    public function testGradeName_MinPlus1_TwoChars_Passes(): void
    {
        $result = GradeValidator::validateGradeName('AB');
        $this->assertNull($result,
            '2-character grade name should pass validation.');
    }

    // ── Mid ────────────────────────────────────────────────────────────────

    /**
     * @test
     * Mid: typical grade name 'Year 5' (6 chars) must pass.
     */
    public function testGradeName_Mid_TypicalInput_Passes(): void
    {
        $result = GradeValidator::validateGradeName('Year 5');
        $this->assertNull($result,
            "'Year 5' (6 chars) should pass validation.");
    }

    // ── Max Boundary ───────────────────────────────────────────────────────

    /**
     * @test
     * Max-1: 49-character string must pass.
     */
    public function testGradeName_MaxMinus1_49Chars_Passes(): void
    {
        $input  = str_repeat('A', 49);
        $result = GradeValidator::validateGradeName($input);
        $this->assertNull($result,
            '49-character grade name should pass (within VARCHAR(50)).');
    }

    /**
     * @test
     * Max (Boundary): exactly 50 characters must pass.
     */
    public function testGradeName_MaxBoundary_50Chars_Passes(): void
    {
        $input  = str_repeat('A', 50);
        $result = GradeValidator::validateGradeName($input);
        $this->assertNull($result,
            '50-character grade name should pass (at VARCHAR(50) boundary).');
    }

    /**
     * @test
     * Max+1: 51 characters must fail (exceeds VARCHAR(50)).
     */
    public function testGradeName_MaxPlus1_51Chars_Fails(): void
    {
        $input  = str_repeat('A', 51);
        $result = GradeValidator::validateGradeName($input);
        $this->assertNotNull($result,
            '51-character grade name should fail (exceeds VARCHAR(50)).');
    }

    // ── Extreme Max ────────────────────────────────────────────────────────

    /**
     * @test
     * Extreme Max: 255-character string must fail.
     */
    public function testGradeName_ExtremeMax_255Chars_Fails(): void
    {
        $input  = str_repeat('A', 255);
        $result = GradeValidator::validateGradeName($input);
        $this->assertNotNull($result,
            '255-character grade name should fail (far exceeds VARCHAR(50)).');
    }

    // ── Invalid Data Type / Security ───────────────────────────────────────

    /**
     * @test
     * XSS attempt: script tag is a valid string (stored and escaped by htmlspecialchars).
     * The validator does NOT reject it — output escaping handles XSS, not input filtering.
     */
    public function testGradeName_XssAttempt_PassesValidation_EscapedOnOutput(): void
    {
        $input   = '<script>alert(1)</script>';
        $result  = GradeValidator::validateGradeName($input);
        $this->assertNull($result,
            'XSS string is a valid input string — escaping is done at output, not input.');

        // Verify htmlspecialchars escapes it correctly
        $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString('<script>', $escaped,
            'htmlspecialchars() must escape the script tag before rendering.');
    }

    /**
     * @test
     * SQL injection attempt: PDO prepared statements prevent execution.
     * Validator treats it as a normal string.
     */
    public function testGradeName_SqlInjection_TreatedAsLiteralString(): void
    {
        $input  = "Year 1'; DROP TABLE tblGrade;--";
        $result = GradeValidator::validateGradeName($input);
        // Length is 31 chars — passes length validation
        $this->assertNull($result,
            'SQL injection string is a valid-length string; PDO prevents injection at DB layer.');
    }

    /**
     * @test
     * Duplicate name check: validateGradeName only checks format, not uniqueness.
     * Uniqueness is enforced by sp_CheckGradeNameExists — this confirms separation of concerns.
     */
    public function testGradeName_DuplicateCheck_NotResponsibilityOfValidator(): void
    {
        // 'Year 1' passes format validation — duplicate check is at DB layer
        $result = GradeValidator::validateGradeName('Year 1');
        $this->assertNull($result,
            'Format validator should pass Year 1 — duplicate check is done by sp_CheckGradeNameExists.');
    }
}


// ══════════════════════════════════════════════════════════════════════════════
/**
 * Test suite for GradeValidator::validateDescription()
 *
 * Boundary values based on VARCHAR(255) DEFAULT NULL:
 *   Min boundary = 0 characters (NULL — optional)
 *   Max boundary = 255 characters
 */
// ══════════════════════════════════════════════════════════════════════════════
class GradeDescriptionValidationTest extends TestCase
{
    // ── Extreme Min (optional field) ───────────────────────────────────────

    /**
     * @test
     * Extreme Min: empty string must pass (field is optional — NULL allowed).
     */
    public function testDescription_ExtremeMin_EmptyString_Passes(): void
    {
        $result = GradeValidator::validateDescription('');
        $this->assertNull($result,
            'Empty description should pass — field is optional (DEFAULT NULL).');
    }

    /**
     * @test
     * prepareDescription() converts empty string to NULL for storage.
     */
    public function testDescription_EmptyString_ConvertedToNull(): void
    {
        $prepared = GradeValidator::prepareDescription('');
        $this->assertNull($prepared,
            'Empty description should be converted to NULL for DB storage.');
    }

    /**
     * @test
     * Whitespace-only input should be treated as NULL.
     */
    public function testDescription_WhitespaceOnly_ConvertedToNull(): void
    {
        $prepared = GradeValidator::prepareDescription('   ');
        $this->assertNull($prepared,
            'Whitespace-only description should trim to empty and be stored as NULL.');
    }

    // ── Min Boundary ───────────────────────────────────────────────────────

    /**
     * @test
     * Min (Boundary): 1-character description must pass.
     */
    public function testDescription_MinBoundary_OneChar_Passes(): void
    {
        $result = GradeValidator::validateDescription('A');
        $this->assertNull($result,
            '1-character description should pass validation.');
    }

    /**
     * @test
     * Min+1: 2-character description must pass.
     */
    public function testDescription_MinPlus1_TwoChars_Passes(): void
    {
        $result = GradeValidator::validateDescription('AB');
        $this->assertNull($result,
            '2-character description should pass validation.');
    }

    // ── Mid ────────────────────────────────────────────────────────────────

    /**
     * @test
     * Mid: typical description (31 chars) must pass.
     */
    public function testDescription_Mid_TypicalInput_Passes(): void
    {
        $result = GradeValidator::validateDescription('First year of secondary school');
        $this->assertNull($result,
            'Typical 30-char description should pass validation.');
    }

    // ── Max Boundary ───────────────────────────────────────────────────────

    /**
     * @test
     * Max-1: 254-character description must pass.
     */
    public function testDescription_MaxMinus1_254Chars_Passes(): void
    {
        $input  = str_repeat('D', 254);
        $result = GradeValidator::validateDescription($input);
        $this->assertNull($result,
            '254-character description should pass (within VARCHAR(255)).');
    }

    /**
     * @test
     * Max (Boundary): exactly 255 characters must pass.
     */
    public function testDescription_MaxBoundary_255Chars_Passes(): void
    {
        $input  = str_repeat('D', 255);
        $result = GradeValidator::validateDescription($input);
        $this->assertNull($result,
            '255-character description should pass (at VARCHAR(255) boundary).');
    }

    /**
     * @test
     * Max+1: 256 characters must fail.
     */
    public function testDescription_MaxPlus1_256Chars_Fails(): void
    {
        $input  = str_repeat('D', 256);
        $result = GradeValidator::validateDescription($input);
        $this->assertNotNull($result,
            '256-character description should fail (exceeds VARCHAR(255)).');
    }

    // ── Extreme Max ────────────────────────────────────────────────────────

    /**
     * @test
     * Extreme Max: 1000-character description must fail.
     */
    public function testDescription_ExtremeMax_1000Chars_Fails(): void
    {
        $input  = str_repeat('D', 1000);
        $result = GradeValidator::validateDescription($input);
        $this->assertNotNull($result,
            '1000-character description should fail (far exceeds VARCHAR(255)).');
    }

    // ── Invalid Data Type / Security ───────────────────────────────────────

    /**
     * @test
     * XSS attempt: script tag is a valid string — htmlspecialchars handles output.
     */
    public function testDescription_XssAttempt_PassesValidation_EscapedOnOutput(): void
    {
        $input  = '<script>alert(1)</script>';
        $result = GradeValidator::validateDescription($input);
        $this->assertNull($result,
            'XSS string in description is valid input — output escaping handles XSS.');

        $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString('<script>', $escaped,
            'htmlspecialchars() must escape script tags in description output.');
    }

    /**
     * @test
     * Special characters (ampersand, dash) should be stored and escaped correctly.
     */
    public function testDescription_SpecialChars_StoredAndEscapedCorrectly(): void
    {
        $input   = "Year 1 & Term — Foundation Stage";
        $result  = GradeValidator::validateDescription($input);
        $this->assertNull($result,
            'Description with & and — should pass validation.');

        $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('&amp;', $escaped,
            'Ampersand should be escaped to &amp; for safe HTML output.');
    }

    /**
     * @test
     * prepareDescription() returns the trimmed string when non-empty.
     */
    public function testDescription_NonEmpty_ReturnsTrimmedString(): void
    {
        $prepared = GradeValidator::prepareDescription('  First year  ');
        $this->assertSame('First year', $prepared,
            'prepareDescription() should trim and return the non-empty string.');
    }
}
