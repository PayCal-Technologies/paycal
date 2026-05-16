<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Tabularium;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the Tabularium pure-PHP PDF generator.
 *
 * Purpose: Verify input sanitization hardening (Priority 1), word-wrap (Priority 3),
 * overflow detection, auto page-break via safeRow() (Priority 2), document metadata
 * (Priority 4), and basic PDF 1.4 structural correctness of the output string.
 *
 * These tests exercise the public API without inspecting internal state directly;
 * assertions are made against public return values and the emitted PDF byte stream.
 *
 * Group: unit
 */
#[Group('unit')]
final class TabulariumTest extends TestCase
{
  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  private function newPdf(): Tabularium
  {
    $t = new Tabularium(Tabularium::PAGE_A4);
    $t->addPage();
    $t->setFont('H', 10);
    return $t;
  }

  // ---------------------------------------------------------------------------
  // Priority 1: sanitizeText() hardening
  // ---------------------------------------------------------------------------

  public function testSanitizeTextEmptyString(): void
  {
    $t = new Tabularium();
    $this->assertSame('', $t->sanitizeText(''));
  }

  public function testSanitizeTextNullByteStripped(): void
  {
    $t = new Tabularium();
    $this->assertSame('hello world', $t->sanitizeText("hello\x00 world"));
  }

  public function testSanitizeTextControlCharsStripped(): void
  {
    $t = new Tabularium();
    // 0x01–0x08 (SOH–BS), 0x0B (VT), 0x0C (FF), 0x0E–0x1F, 0x7F (DEL)
    $this->assertSame('AB', $t->sanitizeText("A\x01\x07\x08\x0B\x0C\x0E\x1F\x7FB"));
  }

  public function testSanitizeTextNewlinesNormalizedToSpace(): void
  {
    $t = new Tabularium();
    $this->assertSame('line one line two', $t->sanitizeText("line one\nline two"));
    $this->assertSame('line one line two', $t->sanitizeText("line one\r\nline two"));
    $this->assertSame('line one line two', $t->sanitizeText("line one\rline two"));
  }

  public function testSanitizeTextParenthesesPassThrough(): void
  {
    // sanitizeText() does NOT escape PDF syntax — that is escape()'s job
    // Parentheses in normal text should survive sanitizeText() as-is
    $t = new Tabularium();
    $this->assertSame('(hello)', $t->sanitizeText('(hello)'));
  }

  public function testSanitizeTextBackslashPassesThrough(): void
  {
    $t = new Tabularium();
    $this->assertSame('a\\b', $t->sanitizeText('a\\b'));
  }

  public function testSanitizeTextWinAnsiControlRangeReplacedWithSpace(): void
  {
    $t = new Tabularium();
    // 0x80–0x9F: Windows-1252 control bytes — not valid WinAnsiEncoding printable range
    $result = $t->sanitizeText("A\x85B");   // 0x85 = NEXT LINE in Windows-1252
    $this->assertSame('A B', $result);
  }

  public function testSanitizeTextAccentedLatinTransliterated(): void
  {
    $t = new Tabularium();
    $this->assertSame('Rene Dupont', $t->sanitizeText('René Dupont'));
    $this->assertSame('Munoz', $t->sanitizeText('Muñoz'));
    $this->assertSame('uber', $t->sanitizeText('über'));
  }

  public function testSanitizeTextRunsOfSpacesCollapsed(): void
  {
    $t = new Tabularium();
    $this->assertSame('a b c', $t->sanitizeText("a  \t b   c"));
  }

  public function testSanitizeTextTrimsLeadingTrailingSpace(): void
  {
    $t = new Tabularium();
    $this->assertSame('hello', $t->sanitizeText('  hello  '));
  }

  public function testSanitizeTextSmartQuotesSubstituted(): void
  {
    $t = new Tabularium();
    // U+2018 / U+2019 (curly single quotes) → ASCII apostrophe (both replaced)
    $this->assertSame("'It's good", $t->sanitizeText("‘It’s good"));
    // U+201C / U+201D (curly double quotes) → ASCII double quote
    $this->assertSame('"quoted"', $t->sanitizeText("“quoted”"));
    // U+2013 (en-dash) and U+2014 (em-dash) → hyphen
    $this->assertSame('a-b', $t->sanitizeText("a–b"));
    $this->assertSame('a-b', $t->sanitizeText("a—b"));
    // U+2026 (ellipsis) → three dots
    $this->assertSame('etc...', $t->sanitizeText("etc…"));
  }

  // ---------------------------------------------------------------------------
  // Priority 3: wrapText() word-wrap
  // ---------------------------------------------------------------------------

  public function testWrapTextShortStringFitsOnOneLine(): void
  {
    $t = $this->newPdf();
    $lines = $t->wrapText('Hello', 200.0);
    $this->assertCount(1, $lines);
    $this->assertSame('Hello', $lines[0]);
  }

  public function testWrapTextLongStringBreaksAtWordBoundary(): void
  {
    $t = $this->newPdf();
    // At 10pt Helvetica, "Alpha" ≈ 24 pt and "Alpha Bravo" ≈ 53 pt.
    // maxWidth=40 forces a break between words without triggering per-character splitting.
    $lines = $t->wrapText('Alpha Bravo Charlie', 40.0);
    $this->assertGreaterThan(1, count($lines));
    // Word-boundary split: each element is a whole word, so space-join restores original
    $this->assertSame('Alpha Bravo Charlie', implode(' ', $lines));
  }

  public function testWrapTextEmptyStringReturnsOneEmptyLine(): void
  {
    $t = $this->newPdf();
    $lines = $t->wrapText('', 100.0);
    $this->assertSame([''], $lines);
  }

  public function testWrapTextSanitizesInput(): void
  {
    $t = $this->newPdf();
    // Null byte should be stripped before wrapping
    $lines = $t->wrapText("hello\x00world", 200.0);
    $this->assertSame(['helloworld'], $lines);
  }

  // ---------------------------------------------------------------------------
  // Priority 2: safeRow() auto page-break
  // ---------------------------------------------------------------------------

  public function testSafeRowNoPageBreakWhenSpaceAvailable(): void
  {
    $t = $this->newPdf();
    $initialPages = $t->getPageCount();
    $t->safeRow([['Label', 200.0], ['Value', 100.0]], 16.0);
    $this->assertSame($initialPages, $t->getPageCount());
  }

  public function testSafeRowTriggersPageBreakWhenOverflowing(): void
  {
    $t = $this->newPdf();
    // Push cursor near the bottom margin so willOverflow(16) triggers.
    // setY() is raw; marginTop + getPrintHeight() = pageHeight - marginBottom.
    $t->setY($t->getY() + $t->getPrintHeight() - 5.0);
    $pagesBefore = $t->getPageCount();
    $t->safeRow([['Overflow', 200.0]], 16.0);
    $this->assertSame($pagesBefore + 1, $t->getPageCount());
  }

  public function testSafeRowCallsOnNewPageCallback(): void
  {
    $t = $this->newPdf();
    $t->setY($t->getY() + $t->getPrintHeight() - 5.0);
    $callbackCalled = false;
    $t->safeRow([['X', 100.0]], 16.0, '1', false, function () use (&$callbackCalled): void {
      $callbackCalled = true;
    });
    $this->assertTrue($callbackCalled);
  }

  // ---------------------------------------------------------------------------
  // Priority 4: setMeta() document metadata
  // ---------------------------------------------------------------------------

  public function testSetMetaAppearsInOutputString(): void
  {
    $t = $this->newPdf();
    $t->setMeta('My Title', 'Test Author', 'Test Subject');
    $pdf = $t->output('S');
    $this->assertStringContainsString('/Title (My Title)', $pdf);
    $this->assertStringContainsString('/Author (Test Author)', $pdf);
    $this->assertStringContainsString('/Subject (Test Subject)', $pdf);
    $this->assertStringContainsString('/Creator (Tabularium/PayCal)', $pdf);
  }

  public function testSetMetaOmittedWhenEmpty(): void
  {
    $t = $this->newPdf();
    $pdf = $t->output('S');
    $this->assertStringNotContainsString('/Info', $pdf);
    $this->assertStringNotContainsString('/Creator', $pdf);
  }

  public function testSetMetaEscapesParensInValues(): void
  {
    $t = $this->newPdf();
    $t->setMeta('Report (Q1)');
    $pdf = $t->output('S');
    // Parentheses inside the title value must be escaped for PDF literal strings
    $this->assertStringContainsString('/Title (Report \\(Q1\\))', $pdf);
  }

  public function testSetMetaSanitizesControlCharsInTitle(): void
  {
    $t = $this->newPdf();
    // Null byte (0x00) and SOH (0x01) are stripped (not replaced), so adjacent chars merge.
    $t->setMeta("Title\x00with\x01null");
    $pdf = $t->output('S');
    $this->assertStringContainsString('/Title (Titlewithnull)', $pdf);
  }

  // ---------------------------------------------------------------------------
  // textWidth() — Courier monospace fix
  // ---------------------------------------------------------------------------

  public function testTextWidthCourierIsMonospace(): void
  {
    $t = new Tabularium();
    $t->addPage();
    $t->setFont('C', 10);  // Courier
    // Courier at 10pt: 5 chars × 600 units × 10 / 1000 = 30.0 pt
    $this->assertEqualsWithDelta(30.0, $t->textWidth('HELLO'), 0.001);
    // Width must scale linearly with character count
    $this->assertEqualsWithDelta(60.0, $t->textWidth('HELLOHELLO'), 0.001);
  }

  // ---------------------------------------------------------------------------
  // PDF structure: output string correctness
  // ---------------------------------------------------------------------------

  public function testOutputStringHasPdfHeader(): void
  {
    $t = $this->newPdf();
    $pdf = $t->output('S');
    $this->assertStringStartsWith('%PDF-1.4', $pdf);
  }

  public function testOutputStringHasEofMarker(): void
  {
    $t = $this->newPdf();
    $pdf = $t->output('S');
    $this->assertStringContainsString('%%EOF', $pdf);
  }

  public function testOutputStringHasXrefAndTrailer(): void
  {
    $t = $this->newPdf();
    $pdf = $t->output('S');
    $this->assertStringContainsString('xref', $pdf);
    $this->assertStringContainsString('trailer', $pdf);
    $this->assertStringContainsString('/Root 1 0 R', $pdf);
  }

  public function testOutputStringMultiplePages(): void
  {
    $t = new Tabularium();
    $t->addPage();
    $t->setFont('H', 10);
    $t->text(36, 50, 'Page one');
    $t->addPage();
    $t->text(36, 50, 'Page two');
    $pdf = $t->output('S');
    $this->assertSame(2, $t->getPageCount());
    $this->assertStringContainsString('/Count 2', $pdf);
  }

  public function testOutputThrowsWithNoPages(): void
  {
    $t = new Tabularium();
    $this->expectException(\LogicException::class);
    $t->output('S');
  }

  public function testOutputThrowsOnInvalidMode(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->output('X');
  }

  public function testOutputFModeThrowsOnPathTraversal(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->output('F', '/tmp/../etc/passwd');
  }

  public function testOutputFModeThrowsOnNonexistentDirectory(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->output('F', '/does/not/exist/document.pdf');
  }

  public function testOutputFModeThrowsOnRelativePath(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->output('F', 'relative/path/document.pdf');
  }

  // ---------------------------------------------------------------------------
  // Input validation guards (Priority 4)
  // ---------------------------------------------------------------------------

  public function testSetFontSizeZeroThrows(): void
  {
    $t = new Tabularium();
    $this->expectException(\InvalidArgumentException::class);
    $t->setFontSize(0.0);
  }

  public function testSetFontSizeNegativeThrows(): void
  {
    $t = new Tabularium();
    $this->expectException(\InvalidArgumentException::class);
    $t->setFontSize(-1.0);
  }

  public function testSetLineWidthZeroThrows(): void
  {
    $t = new Tabularium();
    $this->expectException(\InvalidArgumentException::class);
    $t->setLineWidth(0.0);
  }

  public function testSetLineWidthNegativeThrows(): void
  {
    $t = new Tabularium();
    $this->expectException(\InvalidArgumentException::class);
    $t->setLineWidth(-0.5);
  }

  public function testSetFontNegativeSizeThrows(): void
  {
    $t = new Tabularium();
    $this->expectException(\InvalidArgumentException::class);
    $t->setFont('', -10.0);
  }

  public function testRgbClampingDoesNotThrow(): void
  {
    // Values outside 0–255 should be silently clamped, not throw
    $t = new Tabularium();
    $t->setFillColor(-10, 300, 128);
    $t->setTextColor(-1, -1, -1);
    $t->setDrawColor(256, 256, 256);
    // If we get here, no exception was thrown — assert something to avoid risky test
    $this->assertTrue(true);
  }

  public function testRgbClampedValuesProduceValidPdf(): void
  {
    $t = new Tabularium();
    $t->addPage();
    $t->setFont('', 10);
    $t->setFillColor(-50, 300, 128);  // clamped to 0, 255, 128
    $t->cell(100.0, 16.0, 'Clamped', '1', 'L', true);
    $pdf = $t->output('S');
    // Clamped values must stay in 0.000–1.000 range; no negative or >1 component
    $this->assertStringNotContainsString('-0.', $pdf);
    $this->assertMatchesRegularExpression('/0\.000 1\.000 0\.502 rg/', $pdf);
  }

  // ---------------------------------------------------------------------------
  // MAX_PAGES cap (Priority 5)
  // ---------------------------------------------------------------------------

  public function testAddPageThrowsAtMaxPages(): void
  {
    $this->expectException(\OverflowException::class);
    $t = new Tabularium();
    for ($i = 0; $i <= Tabularium::MAX_PAGES; $i++) {
      $t->addPage();
    }
  }

  // ---------------------------------------------------------------------------
  // MAX_TEXT_BYTES truncation (Priority 5)
  // ---------------------------------------------------------------------------

  public function testSanitizeTextTruncatesOversizedInput(): void
  {
    $t = new Tabularium();
    $long = str_repeat('A', Tabularium::MAX_TEXT_BYTES + 100);
    $result = $t->sanitizeText($long);
    $this->assertLessThanOrEqual(Tabularium::MAX_TEXT_BYTES, strlen($result));
  }

  // ---------------------------------------------------------------------------
  // multiCell() return value
  // ---------------------------------------------------------------------------

  public function testMultiCellReturnsTotalHeight(): void
  {
    $t = $this->newPdf();
    $lineH = 14.0;
    // Force a wrap by using a very narrow cell width
    $height = $t->multiCell(5.0, $lineH, 'Alpha Bravo Charlie Delta');
    $this->assertGreaterThanOrEqual($lineH, $height);
    // Height must be a multiple of $lineH
    $this->assertEqualsWithDelta(0.0, fmod($height, $lineH), 0.001);
  }

  // ---------------------------------------------------------------------------
  // Drawing before addPage() (guard on out())
  // ---------------------------------------------------------------------------

  public function testTextBeforeAddPageThrows(): void
  {
    $t = new Tabularium();
    $t->setFont('', 10);
    $this->expectException(\LogicException::class);
    $t->text(36.0, 50.0, 'Hello');
  }

  public function testCellBeforeAddPageThrows(): void
  {
    $t = new Tabularium();
    $t->setFont('', 10);
    $this->expectException(\LogicException::class);
    $t->cell(100.0, 16.0, 'Test');
  }

  // ---------------------------------------------------------------------------
  // Constructor validation
  // ---------------------------------------------------------------------------

  public function testConstructorThrowsOnZeroPageWidth(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    new Tabularium([0.0, 841.89]);
  }

  public function testConstructorThrowsOnNegativePageHeight(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    new Tabularium([595.28, -1.0]);
  }

  public function testConstructorThrowsOnNegativeMargin(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    new Tabularium(Tabularium::PAGE_A4, -1.0);
  }

  public function testConstructorThrowsWhenMarginsExceedPageWidth(): void
  {
    // 300 + 300 = 600 > 595.28 (A4 width)
    $this->expectException(\InvalidArgumentException::class);
    new Tabularium(Tabularium::PAGE_A4, 300.0, 300.0);
  }

  public function testConstructorDefaultsAreValid(): void
  {
    // new Tabularium() with default PAGE_A4 and 36pt margins must not throw
    $t = new Tabularium();
    $this->assertSame(0, $t->getPageCount());
  }

  // ---------------------------------------------------------------------------
  // rule() line width validation
  // ---------------------------------------------------------------------------

  public function testRuleZeroLineWidthThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->rule(0.0);
  }

  public function testRuleNegativeLineWidthThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->rule(-1.0);
  }

  public function testRulePositiveLineWidthDoesNotThrow(): void
  {
    $t = $this->newPdf();
    $t->rule(0.5);
    $this->assertTrue(true);
  }

  // ---------------------------------------------------------------------------
  // rect() dimension validation
  // ---------------------------------------------------------------------------

  public function testRectNegativeWidthThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->rect(10.0, 10.0, -50.0, 20.0);
  }

  public function testRectZeroHeightThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->rect(10.0, 10.0, 50.0, 0.0);
  }

  public function testRectNegativeHeightThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->rect(10.0, 10.0, 50.0, -10.0);
  }

  // ---------------------------------------------------------------------------
  // cell() height validation
  // ---------------------------------------------------------------------------

  public function testCellZeroHeightThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->cell(100.0, 0.0, 'Test');
  }

  public function testCellNegativeHeightThrows(): void
  {
    $t = $this->newPdf();
    $this->expectException(\InvalidArgumentException::class);
    $t->cell(100.0, -5.0, 'Test');
  }

  // ---------------------------------------------------------------------------
  // wrapText() oversized single-word splitting
  // ---------------------------------------------------------------------------

  public function testWrapTextSplitsHugeSingleWord(): void
  {
    $t = $this->newPdf();
    // At 10pt Helvetica, a single ASCII char is ~5.6 pt wide; at maxWidth=1 every char
    // must be placed on its own line. Exact split count is at least 12 for a 12-char word.
    $lines = $t->wrapText('ABCDEFGHIJKL', 1.0);
    $this->assertGreaterThanOrEqual(2, count($lines));
    // All characters must be preserved across lines
    $this->assertSame('ABCDEFGHIJKL', implode('', $lines));
  }

  public function testWrapTextHugeWordFollowedByShortWords(): void
  {
    $t = $this->newPdf();
    // Narrow maxWidth forces the long word to split; "hi" fits on its own line
    $lines = $t->wrapText('VERYLONGWORD hi', 1.0);
    $allText = implode('', $lines);
    // All non-space chars must be present
    $this->assertStringContainsString('VERYLONGWORD', $allText);
    $this->assertStringContainsString('hi', $allText);
  }
}
