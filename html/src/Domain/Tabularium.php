<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Tabularium.php
 *
 * Purpose: Pure-PHP tabular PDF document generator — PayCal's internal record-rendering engine.
 *
 * Named after the Tabulārium (78 BC), Rome's official public records office (archives) built on
 * the Capitoline Hill by consul Quintus Lutatius Catulus. The Tabularium served as the central
 * state repository for laws, senatorial decrees (senatus consulta), treaties, and financial
 * records — inscribed on bronze and wooden tablets (tabulae) for permanence and authority.
 * Its massive arched façade, featuring early Doric engaged columns, is an architectural
 * precursor to the Colosseum; its lower levels survive today inside the Palazzo Senatorio
 * (Rome City Hall on the Capitoline). The ruins offer one of the finest vantage points over
 * the ancient center of the Roman Forum.
 *
 * Just as the Tabularium was the bureaucratic backbone of the Republic — the place where Rome's
 * legal memory and institutional records were preserved — this class is PayCal's engine for
 * producing authoritative, structured document output: earnings reports, pay summaries, and
 * tabular records rendered as permanent PDF artifacts.
 *
 * Implementation notes:
 * - Zero external dependencies. Pure PHP string operations only; no font files required.
 * - Emits a PDF 1.4-compliant document with a complete cross-reference table and trailer.
 * - Built-in Type1 fonts only (Helvetica family, Courier). No font file embedding.
 * - Content streams use standard PDF graphics operators: BT/ET, Tf, Td, Tj, re, f, S, RG, rg.
 * - Coordinate origin is top-left (user-facing). Internal Y axis is flipped for PDF convention.
 * - Units: points (pt). 1 pt = 1/72 inch. A4 ≈ 595 × 842 pt. Use PAGE_* constants.
 * - Helvetica AFM character width tables are embedded for accurate text measurement.
 * - Multi-byte / non-Latin text is not supported; content is treated as WinAnsiEncoding bytes.
 *
 * Companion to EmailGarum (outbound email) and ContentView (HTML → CSS print path).
 * Use Tabularium when programmatic multi-page layout control is required; ContentView's
 * CSS @media print path remains sufficient for simple single-page document rendering.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class Tabularium
{
  // ---------------------------------------------------------------------------
  // Page size presets in points [width, height]
  // ---------------------------------------------------------------------------

  /** A4 portrait — 210 × 297 mm */
  public const PAGE_A4     = [595.28, 841.89];
  /** US Letter portrait — 8.5 × 11 in */
  public const PAGE_LETTER = [612.00, 792.00];
  /** US Legal portrait — 8.5 × 14 in */
  public const PAGE_LEGAL  = [612.00, 1008.00];

  /**
   * Maximum pages allowed per document.
   * Prevents unbounded memory growth in user-generated export paths.
   */
  public const MAX_PAGES = 500;

  /**
   * Maximum byte length accepted by sanitizeText() / escape() / cell() / text().
   * Strings longer than this are truncated before processing.
   * At 10pt Helvetica, 4 096 bytes is far more than fits on any single line or cell.
   */
  public const MAX_TEXT_BYTES = 4096;

  // ---------------------------------------------------------------------------
  // Built-in font map: style key → PDF base font name
  // ---------------------------------------------------------------------------

  /** @var array<string, string> */
  private const FONT_MAP = [
    ''   => 'Helvetica',
    'B'  => 'Helvetica-Bold',
    'I'  => 'Helvetica-Oblique',
    'BI' => 'Helvetica-BoldOblique',
    'IB' => 'Helvetica-BoldOblique',
    'C'  => 'Courier',
    'CB' => 'Courier-Bold',
  ];

  // ---------------------------------------------------------------------------
  // Helvetica AFM character widths (per 1000 units, WinAnsiEncoding, ASCII 32–126)
  // Source: Adobe Type 1 Font Program Specification — Helvetica metrics.
  // Used to compute string width for text placement and cell overflow detection.
  // ---------------------------------------------------------------------------

  /** @var array<int, int> Helvetica / Helvetica-Oblique */
  private const W_REGULAR = [
     32 => 278,  33 => 278,  34 => 355,  35 => 556,  36 => 556,  37 => 889,  38 => 667,  39 => 222,
     40 => 333,  41 => 333,  42 => 389,  43 => 584,  44 => 278,  45 => 333,  46 => 278,  47 => 278,
     48 => 556,  49 => 556,  50 => 556,  51 => 556,  52 => 556,  53 => 556,  54 => 556,  55 => 556,
     56 => 556,  57 => 556,  58 => 278,  59 => 278,  60 => 584,  61 => 584,  62 => 584,  63 => 556,
     64 => 1015, 65 => 667,  66 => 667,  67 => 722,  68 => 722,  69 => 667,  70 => 611,  71 => 778,
     72 => 722,  73 => 278,  74 => 500,  75 => 667,  76 => 556,  77 => 833,  78 => 722,  79 => 778,
     80 => 667,  81 => 778,  82 => 722,  83 => 667,  84 => 611,  85 => 722,  86 => 667,  87 => 944,
     88 => 667,  89 => 667,  90 => 611,  91 => 278,  92 => 278,  93 => 278,  94 => 469,  95 => 556,
     96 => 222,  97 => 556,  98 => 556,  99 => 500, 100 => 556, 101 => 556, 102 => 278, 103 => 556,
    104 => 556, 105 => 222, 106 => 222, 107 => 500, 108 => 222, 109 => 833, 110 => 556, 111 => 556,
    112 => 556, 113 => 556, 114 => 333, 115 => 500, 116 => 278, 117 => 556, 118 => 500, 119 => 722,
    120 => 500, 121 => 500, 122 => 500, 123 => 334, 124 => 260, 125 => 334, 126 => 584,
  ];

  /** @var array<int, int> Helvetica-Bold / Helvetica-BoldOblique */
  private const W_BOLD = [
     32 => 278,  33 => 333,  34 => 474,  35 => 556,  36 => 556,  37 => 889,  38 => 722,  39 => 278,
     40 => 333,  41 => 333,  42 => 389,  43 => 584,  44 => 278,  45 => 333,  46 => 278,  47 => 278,
     48 => 556,  49 => 556,  50 => 556,  51 => 556,  52 => 556,  53 => 556,  54 => 556,  55 => 556,
     56 => 556,  57 => 556,  58 => 333,  59 => 333,  60 => 584,  61 => 584,  62 => 584,  63 => 611,
     64 => 975,  65 => 722,  66 => 722,  67 => 722,  68 => 722,  69 => 667,  70 => 611,  71 => 778,
     72 => 722,  73 => 278,  74 => 556,  75 => 722,  76 => 611,  77 => 833,  78 => 722,  79 => 778,
     80 => 667,  81 => 778,  82 => 722,  83 => 667,  84 => 611,  85 => 722,  86 => 667,  87 => 944,
     88 => 667,  89 => 667,  90 => 611,  91 => 333,  92 => 278,  93 => 333,  94 => 584,  95 => 556,
     96 => 278,  97 => 556,  98 => 611,  99 => 556, 100 => 611, 101 => 556, 102 => 333, 103 => 611,
    104 => 611, 105 => 278, 106 => 278, 107 => 556, 108 => 278, 109 => 889, 110 => 611, 111 => 611,
    112 => 611, 113 => 611, 114 => 389, 115 => 556, 116 => 333, 117 => 611, 118 => 556, 119 => 778,
    120 => 556, 121 => 556, 122 => 500, 123 => 389, 124 => 280, 125 => 389, 126 => 584,
  ];

  // ---------------------------------------------------------------------------
  // Document state
  // ---------------------------------------------------------------------------

  private float $pageWidth;
  private float $pageHeight;
  private float $marginLeft;
  private float $marginRight;
  private float $marginTop;
  private float $marginBottom;

  /** @var array<int, string> Content stream accumulator per page (0-indexed) */
  private array $pageStreams = [];

  /** Current page index (-1 = no pages added yet) */
  private int $pageIndex = -1;

  /** Current cursor X in points (top-left origin) */
  private float $curX;

  /** Current cursor Y in points (top-left origin) */
  private float $curY;

  /** PDF font name currently active, e.g. 'Helvetica-Bold' */
  private string $fontName = 'Helvetica';

  /** Whether the current font weight is bold (selects W_BOLD width table) */
  private bool $fontBold = false;

  /** Current font size in points */
  private float $fontSize = 10.0;

  /** Fill (background) RGB, each 0.0–1.0 */
  private float $fillR = 1.0;
  private float $fillG = 1.0;
  private float $fillB = 1.0;

  /** Text (non-stroking) RGB, each 0.0–1.0 */
  private float $textR = 0.0;
  private float $textG = 0.0;
  private float $textB = 0.0;

  /** Draw (stroking) RGB, each 0.0–1.0 */
  private float $drawR = 0.0;
  private float $drawG = 0.0;
  private float $drawB = 0.0;

  /** Current stroke line width in points */
  private float $lineWidth = 0.5;

  /**
   * Fonts registered for this document.
   * Keys = PDF base font name (e.g. 'Helvetica-Bold'), values = resource alias (F1, F2, …).
   *
   * @var array<string, string>
   */
  private array $usedFonts = [];

  /** Document title for the PDF /Info dictionary (empty = omit from output) */
  private string $metaTitle = '';

  /** Document author for the PDF /Info dictionary (empty = omit from output) */
  private string $metaAuthor = '';

  /** Document subject for the PDF /Info dictionary (empty = omit from output) */
  private string $metaSubject = '';

  // ---------------------------------------------------------------------------
  // Constructor
  // ---------------------------------------------------------------------------

  /**
   * Create a new Tabularium document.
   *
   * @param array{0: float, 1: float} $pageSize    [width_pt, height_pt]; use PAGE_* constants
   * @param float                     $marginLeft   Left margin in points (default 36 pt = 0.5 in)
   * @param float                     $marginRight  Right margin in points
   * @param float                     $marginTop    Top margin in points
   * @param float                     $marginBottom Bottom margin in points
   */
  public function __construct(
    array $pageSize = self::PAGE_A4,
    float $marginLeft = 36.0,
    float $marginRight = 36.0,
    float $marginTop = 36.0,
    float $marginBottom = 36.0,
  ) {
    $w = (float) $pageSize[0];
    $h = (float) $pageSize[1];
    if ($w <= 0.0 || $h <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: page width and height must be positive.');
    }
    if ($marginLeft < 0.0 || $marginRight < 0.0 || $marginTop < 0.0 || $marginBottom < 0.0) {
      throw new \InvalidArgumentException('Tabularium: margins must not be negative.');
    }
    if (($marginLeft + $marginRight) >= $w || ($marginTop + $marginBottom) >= $h) {
      throw new \InvalidArgumentException('Tabularium: combined margins must be less than the page dimension.');
    }
    $this->pageWidth    = $w;
    $this->pageHeight   = $h;
    $this->marginLeft   = $marginLeft;
    $this->marginRight  = $marginRight;
    $this->marginTop    = $marginTop;
    $this->marginBottom = $marginBottom;
    $this->curX = $marginLeft;
    $this->curY = $marginTop;
  }

  // ---------------------------------------------------------------------------
  // Metadata
  // ---------------------------------------------------------------------------

  /**
   * Set PDF document metadata written to the /Info dictionary.
   * Call before output(). Values are sanitized via sanitizeText() before embedding.
   *
   * @param string $title   Document title, e.g. "Earnings Report — Q1 2026"
   * @param string $author  Document author, e.g. "PayCal Technologies Inc."
   * @param string $subject Document subject, e.g. "Payroll Summary"
   */
  public function setMeta(string $title, string $author = '', string $subject = ''): static
  {
    $this->metaTitle   = $this->sanitizeText($title);
    $this->metaAuthor  = $this->sanitizeText($author);
    $this->metaSubject = $this->sanitizeText($subject);
    return $this;
  }

  // ---------------------------------------------------------------------------
  // Page management
  // ---------------------------------------------------------------------------

  /**
   * Add a new page. Resets cursor to (marginLeft, marginTop).
   * Must be called at least once before any drawing method.
   */
  public function addPage(): static
  {
    if (($this->pageIndex + 1) >= self::MAX_PAGES) {
      throw new \OverflowException(sprintf(
        'Tabularium: document exceeds the maximum of %d pages.',
        self::MAX_PAGES
      ));
    }
    $this->pageIndex++;
    $this->pageStreams[$this->pageIndex] = '';
    $this->curX = $this->marginLeft;
    $this->curY = $this->marginTop;
    $this->emitPageState();
    return $this;
  }

  /**
   * Number of pages added so far.
   */
  public function getPageCount(): int
  {
    return $this->pageIndex + 1;
  }

  /**
   * Printable width (page width minus left and right margins) in points.
   */
  public function getPrintWidth(): float
  {
    return $this->pageWidth - $this->marginLeft - $this->marginRight;
  }

  /**
   * Printable height (page height minus top and bottom margins) in points.
   */
  public function getPrintHeight(): float
  {
    return $this->pageHeight - $this->marginTop - $this->marginBottom;
  }

  /**
   * Returns true if advancing $h points from the current Y cursor would exceed the bottom margin.
   * Use to trigger addPage() before drawing a row.
   */
  public function willOverflow(float $h): bool
  {
    return ($this->curY + $h) > ($this->pageHeight - $this->marginBottom);
  }

  // ---------------------------------------------------------------------------
  // Font and style
  // ---------------------------------------------------------------------------

  /**
   * Select a built-in font variant. Optionally sets font size.
   *
   * @param string $style '' (regular) | 'B' (bold) | 'I' (oblique) | 'BI' (bold-oblique) | 'C' (Courier) | 'CB' (Courier-Bold)
   * @param float  $size  Font size in pt; 0 = keep current size
   */
  public function setFont(string $style = '', float $size = 0.0): static
  {
    $this->fontName = self::FONT_MAP[$style] ?? 'Helvetica';
    $this->fontBold = str_contains($style, 'B') || $style === 'CB';
    if ($size > 0.0) {
      $this->fontSize = $size;
    } elseif ($size < 0.0) {
      throw new \InvalidArgumentException('Tabularium: font size must be positive.');
    }
    return $this;
  }

  /**
   * Change font size in points without changing style.
   */
  public function setFontSize(float $size): static
  {
    if ($size <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: font size must be positive.');
    }
    $this->fontSize = $size;
    return $this;
  }

  /**
   * Set fill (background) color. Values are clamped to 0–255.
   */
  public function setFillColor(int $r, int $g, int $b): static
  {
    $this->fillR = max(0, min(255, $r)) / 255.0;
    $this->fillG = max(0, min(255, $g)) / 255.0;
    $this->fillB = max(0, min(255, $b)) / 255.0;
    return $this;
  }

  /**
   * Set text (non-stroking) color. Values are clamped to 0–255.
   */
  public function setTextColor(int $r, int $g, int $b): static
  {
    $this->textR = max(0, min(255, $r)) / 255.0;
    $this->textG = max(0, min(255, $g)) / 255.0;
    $this->textB = max(0, min(255, $b)) / 255.0;
    return $this;
  }

  /**
   * Set draw (stroking / border) color. Values are clamped to 0–255.
   */
  public function setDrawColor(int $r, int $g, int $b): static
  {
    $this->drawR = max(0, min(255, $r)) / 255.0;
    $this->drawG = max(0, min(255, $g)) / 255.0;
    $this->drawB = max(0, min(255, $b)) / 255.0;
    return $this;
  }

  /**
   * Set the line width for borders and rules in points.
   */
  public function setLineWidth(float $w): static
  {
    if ($w <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: line width must be positive.');
    }
    $this->lineWidth = $w;
    return $this;
  }

  // ---------------------------------------------------------------------------
  // Cursor
  // ---------------------------------------------------------------------------

  /**
   * Set cursor X.
   */
  public function setX(float $x): static { $this->curX = $x; return $this; }

  /**
   * Set cursor Y.
   */
  public function setY(float $y): static { $this->curY = $y; return $this; }

  /**
   * Set cursor to (x, y).
   */
  public function setXY(float $x, float $y): static { $this->curX = $x; $this->curY = $y; return $this; }

  /**
   * Current cursor X.
   */
  public function getX(): float { return $this->curX; }

  /**
   * Current cursor Y.
   */
  public function getY(): float { return $this->curY; }

  // ---------------------------------------------------------------------------
  // Content primitives
  // ---------------------------------------------------------------------------

  /**
   * Place text at an absolute position without moving the cursor.
   */
  public function text(float $x, float $y, string $text): static
  {
    $alias = $this->registerFont($this->fontName);
    $this->out('BT');
    $this->out(sprintf('/%s %.2f Tf', $alias, $this->fontSize));
    $this->out(sprintf('%.3f %.3f %.3f rg', $this->textR, $this->textG, $this->textB));
    $this->out(sprintf('%.2f %.2f Td', $x, $this->pdfY($y)));
    $this->out(sprintf('(%s) Tj', $this->escape($text)));
    $this->out('ET');
    return $this;
  }

  /**
   * Advance the cursor to the next line.
   *
   * @param float $h Line height in pt; 0 uses fontSize × 1.2 (20 % leading)
   */
  public function ln(float $h = 0.0): static
  {
    $this->curX  = $this->marginLeft;
    $this->curY += ($h > 0.0) ? $h : $this->fontSize * 1.2;
    return $this;
  }

  /**
   * Draw a full-width horizontal rule at the current Y, then advance Y.
   */
  public function rule(float $lineWidth = 0.5): static
  {
    if ($lineWidth <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: rule() line width must be positive.');
    }
    $y = $this->pdfY($this->curY);
    $this->out(sprintf('%.3f %.3f %.3f RG', $this->drawR, $this->drawG, $this->drawB));
    $this->out(sprintf('%.2f w', $lineWidth));
    $this->out(sprintf(
      '%.2f %.2f m %.2f %.2f l S',
      $this->marginLeft, $y,
      $this->pageWidth - $this->marginRight, $y
    ));
    $this->curY += $lineWidth + 2.0;
    return $this;
  }

  /**
   * Draw a rectangle.
   *
   * @param string $style 'S'=stroke, 'F'=fill, 'DF'/'FD'=fill then stroke
   */
  public function rect(float $x, float $y, float $w, float $h, string $style = 'S'): static
  {
    if ($w <= 0.0 || $h <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: rect() width and height must be positive.');
    }
    $pdfY = $this->pdfY($y + $h);
    $s    = strtoupper($style);
    if ($s === 'F' || $s === 'DF' || $s === 'FD') {
      $this->out(sprintf('%.3f %.3f %.3f rg', $this->fillR, $this->fillG, $this->fillB));
    }
    if ($s === 'S' || $s === 'DF' || $s === 'FD') {
      $this->out(sprintf('%.3f %.3f %.3f RG', $this->drawR, $this->drawG, $this->drawB));
      $this->out(sprintf('%.2f w', $this->lineWidth));
    }
    $op = match ($s) {
      'F'        => 'f',
      'DF', 'FD' => 'B',
      default    => 'S',
    };
    $this->out(sprintf('%.2f %.2f %.2f %.2f re %s', $x, $pdfY, $w, $h, $op));
    return $this;
  }

  // ---------------------------------------------------------------------------
  // Cell and table
  // ---------------------------------------------------------------------------

  /**
   * Output a single cell at the current cursor position.
   *
   * After the call, cursor X advances by $w. To start the next line, call ln() or use row().
   *
   * @param float  $w      Cell width in pt. 0 = extend to right margin.
   * @param float  $h      Cell height in pt
   * @param string $text   Cell text (WinAnsiEncoding / ASCII only)
   * @param string $border '0'=none, '1'=all sides, or any of 'L' 'R' 'T' 'B'
   * @param string $align  'L'=left | 'C'=center | 'R'=right
   * @param bool   $fill   Fill cell background with current fill color
   */
  public function cell(
    float $w,
    float $h,
    string $text = '',
    string $border = '0',
    string $align = 'L',
    bool $fill = false,
  ): static {
    if ($h <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: cell() height must be positive.');
    }
    if ($w <= 0.0) {
      $w = $this->pageWidth - $this->marginRight - $this->curX;
    }
    if ($w <= 0.0) {
      throw new \InvalidArgumentException('Tabularium: cell() computed width is zero — cursor is at or beyond the right margin.');
    }

    $x     = $this->curX;
    $y     = $this->curY;
    $cellB = $this->pdfY($y + $h);  // PDF-space bottom of cell

    // Background fill
    if ($fill) {
      $this->out(sprintf('%.3f %.3f %.3f rg', $this->fillR, $this->fillG, $this->fillB));
      $this->out(sprintf('%.2f %.2f %.2f %.2f re f', $x, $cellB, $w, $h));
      // Restore non-stroking color so subsequent operators use text color
      $this->out(sprintf('%.3f %.3f %.3f rg', $this->textR, $this->textG, $this->textB));
    }

    // Border
    if ($border !== '0' && $border !== '') {
      $this->out(sprintf('%.3f %.3f %.3f RG', $this->drawR, $this->drawG, $this->drawB));
      $this->out(sprintf('%.2f w', $this->lineWidth));
      $x2 = $x + $w;
      $y1 = $this->pdfY($y);  // PDF-space top of cell
      $y2 = $cellB;            // PDF-space bottom of cell
      if ($border === '1') {
        $this->out(sprintf('%.2f %.2f %.2f %.2f re S', $x, $y2, $w, $h));
      } else {
        if (str_contains($border, 'T')) { $this->out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, $y1, $x2, $y1)); }
        if (str_contains($border, 'B')) { $this->out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, $y2, $x2, $y2)); }
        if (str_contains($border, 'L')) { $this->out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, $y1,  $x, $y2)); }
        if (str_contains($border, 'R')) { $this->out(sprintf('%.2f %.2f m %.2f %.2f l S', $x2, $y1, $x2, $y2)); }
      }
    }

    // Text
    if ($text !== '') {
      $padding = 3.0;
      $tw      = $this->textWidth($text);
      $textX   = match ($align) {
        'C'     => $x + ($w - $tw) / 2.0,
        'R'     => $x + $w - $tw - $padding,
        default => $x + $padding,
      };
      // Baseline: centered vertically within cell, adjusted for Helvetica cap-height (~0.72em)
      $textY = $cellB + ($h - $this->fontSize) / 2.0 + $this->fontSize * 0.28;
      $alias = $this->registerFont($this->fontName);
      $this->out('BT');
      $this->out(sprintf('/%s %.2f Tf', $alias, $this->fontSize));
      $this->out(sprintf('%.3f %.3f %.3f rg', $this->textR, $this->textG, $this->textB));
      $this->out(sprintf('%.2f %.2f Td', $textX, $textY));
      $this->out(sprintf('(%s) Tj', $this->escape($text)));
      $this->out('ET');
    }

    $this->curX += $w;
    return $this;
  }

  /**
   * Output a row of cells, then advance Y by $h and reset X to the left margin.
   *
   * Each cell entry: [string $text, float $widthPt, string $align = 'L']
   *
   * Example:
   *   $pdf->row([['Site', 180.0, 'L'], ['Hours', 60.0, 'R'], ['Gross Pay', 100.0, 'R']], 16.0);
   *
   * @param array<int, array{0: string, 1: float, 2?: string}> $cells
   * @param float  $h      Row height in pt
   * @param string $border Border spec passed to cell()
   * @param bool   $fill   Fill cell backgrounds with current fill color
   */
  public function row(
    array $cells,
    float $h = 16.0,
    string $border = '1',
    bool $fill = false,
  ): static {
    $startX = $this->curX;
    foreach ($cells as $cell) {
      $text  = (string) $cell[0];
      $width = (float)  $cell[1];
      $align = (string) ($cell[2] ?? 'L');
      $this->cell($width, $h, $text, $border, $align, $fill);
    }
    $this->curX  = $startX;
    $this->curY += $h;
    return $this;
  }

  /**
   * Like row(), but checks willOverflow() first and calls addPage() automatically.
   *
   * When a page break occurs, $onNewPage is invoked (if provided) immediately after
   * addPage() — use this callback to re-draw repeating table header rows.
   *
   * Example:
   *   $header = fn($pdf) => $pdf->row([['Site', 180], ['Hours', 60], ['Gross', 100]], 16, '1', true);
   *   foreach ($rows as $r) { $pdf->safeRow($r, 16, '1', false, $header); }
   *
   * @param array<int, array{0: string, 1: float, 2?: string}> $cells
   * @param callable(static): void|null                        $onNewPage  Called after automatic addPage()
   */
  public function safeRow(
    array $cells,
    float $h = 16.0,
    string $border = '1',
    bool $fill = false,
    ?callable $onNewPage = null,
  ): static {
    if ($this->pageIndex >= 0 && $this->willOverflow($h)) {
      $this->addPage();
      if ($onNewPage !== null) {
        $onNewPage($this);
      }
    }
    return $this->row($cells, $h, $border, $fill);
  }

  /**
   * Output a multi-line word-wrapped cell, advancing Y for each wrapped line.
   * The cursor X is reset to the cell's starting X after every line.
   * Input text is sanitized and wrapped at word boundaries within $w.
   *
   * Border drawing: '1' produces an outer box (T on first line, B on last, L+R on all).
   * Side-spec borders (e.g. 'LR') are applied to every line unchanged.
   *
   * @param float  $w      Cell width in pt; 0 = extend to right margin
   * @param float  $lineH  Height of each text line in pt
   * @param string $text   Cell text; wrapped at word boundaries
   * @param string $border '0'=none | '1'=outer box | side chars 'L' 'R' 'T' 'B'
   * @param string $align  'L'=left | 'C'=center | 'R'=right
   * @param bool   $fill   Fill cell backgrounds with current fill color
   *
   * @return float Total height consumed in pt (lineCount × $lineH)
   */
  public function multiCell(
    float $w,
    float $lineH,
    string $text = '',
    string $border = '0',
    string $align = 'L',
    bool $fill = false,
  ): float {
    if ($w <= 0.0) {
      $w = $this->pageWidth - $this->marginRight - $this->curX;
    }
    $padding = 3.0;
    $lines   = $this->wrapText($text, $w - $padding * 2.0);
    $startX  = $this->curX;
    $count   = count($lines);
    foreach ($lines as $idx => $line) {
      $isFirst = ($idx === 0);
      $isLast  = ($idx === $count - 1);
      $lineBorder = match ($border) {
        '0', '' => '0',
        '1'     => match (true) {
          $count === 1 => '1',
          $isFirst     => 'LRT',
          $isLast      => 'LRB',
          default      => 'LR',
        },
        default => $border,
      };
      $this->cell($w, $lineH, $line, $lineBorder, $align, $fill);
      $this->curX  = $startX;
      $this->curY += $lineH;
    }
    return (float) ($count * $lineH);
  }

  // ---------------------------------------------------------------------------
  // Text helpers: sanitization, word-wrap, measurement
  // ---------------------------------------------------------------------------

  /**
   * Sanitize a string for use in a PDF content stream (Priority 1 hardening).
   *
   * - Normalizes \r\n and bare \n / \r to a single space (cell text is single-line).
   * - Strips ASCII control characters (0x00–0x08, 0x0B–0x0C, 0x0E–0x1F, 0x7F).
   * - Transliterates common UTF-8 accented Latin characters to ASCII equivalents so
   *   common names and addresses survive WinAnsiEncoding without producing garbage glyphs.
   * - Replaces WinAnsi control-range bytes (0x80–0x9F) with a space.
   * - Collapses runs of spaces produced by the above transformations.
   *
   * WinAnsiEncoding printable high bytes (0xA0–0xFF) are passed through unchanged;
   * built-in Type1 fonts declare /Encoding /WinAnsiEncoding in the font dictionary.
   */
  public function sanitizeText(string $input): string
  {
    // Truncate oversized strings before any further processing
    if (strlen($input) > self::MAX_TEXT_BYTES) {
      $input = substr($input, 0, self::MAX_TEXT_BYTES);
    }

    // Normalize line endings and tabs → space (cell content is single-line)
    $s = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $input);

    // Transliterate common UTF-8 accented Latin characters to ASCII
    static $accents = [
      'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
      'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
      'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D',
      'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
      'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'Th', 'ß' => 'ss',
      'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
      'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
      'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd',
      'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
      'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
      // Common typographic substitutions
      // Keys are the actual Unicode characters (U+2018/19, U+201C/D, U+2013/14, U+2026).
      // PHP single-quoted strings do NOT interpret \u{XXXX} — literal chars must be used.
      '‘' => "'", '’' => "'",
      '“' => '"', '”' => '"',
      '–' => '-',  '—' => '-',
      '…' => '...',
    ];
    $s = strtr($s, $accents);

    // Strip ASCII control chars (0x00–0x08, 0x0B–0x0C, 0x0E–0x1F, 0x7F)
    $s = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);

    // Replace WinAnsi control range (0x80–0x9F) with space
    $s = (string) preg_replace('/[\x80-\x9F]/', ' ', $s);

    // Collapse multiple spaces
    $s = (string) preg_replace('/ {2,}/', ' ', $s);

    return trim($s);
  }

  /**
   * Split $text into lines that fit within $maxWidth using the current font and size.
   * Breaks on space boundaries only; individual words wider than $maxWidth are not split.
   * Input is sanitized via sanitizeText() before splitting.
   *
   * @return array<int, string> One or more lines; never empty (returns [''] for blank input)
   */
  public function wrapText(string $text, float $maxWidth): array
  {
    $text  = $this->sanitizeText($text);
    $words = explode(' ', $text);
    $lines = [];
    $line  = '';
    foreach ($words as $word) {
      if ($word === '') {
        continue;
      }
      // Split oversized single words character by character so they never overflow $maxWidth.
      if ($this->textWidth($word) > $maxWidth) {
        if ($line !== '') {
          $lines[] = $line;
          $line    = '';
        }
        $fragment = '';
        for ($i = 0, $len = strlen($word); $i < $len; $i++) {
          $try = $fragment . $word[$i];
          if ($this->textWidth($try) <= $maxWidth) {
            $fragment = $try;
          } else {
            if ($fragment !== '') {
              $lines[] = $fragment;
            }
            $fragment = $word[$i];
          }
        }
        // $word is non-empty (checked above) so the loop always leaves $fragment non-empty.
        $line = $fragment;
        continue;
      }
      $test = ($line === '') ? $word : "$line $word";
      if ($this->textWidth($test) <= $maxWidth) {
        $line = $test;
      } else {
        if ($line !== '') {
          $lines[] = $line;
        }
        $line = $word;
      }
    }
    if ($line !== '') {
      $lines[] = $line;
    }
    return $lines !== [] ? $lines : [''];
  }

  /**
   * Calculate the rendered width of $text in points at the current font and size.
   *
   * Uses embedded Helvetica AFM metrics for Helvetica variants.
   * Courier is monospace — all glyphs are 600 units wide (AFM standard).
   * Defaults to 556 units for unmapped codes (Helvetica average).
   */
  public function textWidth(string $text): float
  {
    // Courier is monospace: all characters are exactly 600 AFM units wide
    if (str_starts_with($this->fontName, 'Courier')) {
      return strlen($text) * 600.0 * $this->fontSize / 1000.0;
    }
    $table = $this->fontBold ? self::W_BOLD : self::W_REGULAR;
    $w = 0;
    for ($i = 0, $len = strlen($text); $i < $len; $i++) {
      $w += $table[ord($text[$i])] ?? 556;
    }
    return $w * $this->fontSize / 1000.0;
  }

  // ---------------------------------------------------------------------------
  // Output
  // ---------------------------------------------------------------------------

  /**
   * Finalize and emit the PDF document.
   *
   * Mode reference:
   *   'I' — Inline in browser. Caller MUST NOT write any further response bytes or call
   *           ob_flush()/flush() after this returns; the PDF stream must be the complete
   *           HTTP response body. Typical usage: call output() as the last statement before
   *           the controller returns, then exit() / die() in the entry script.
   *   'D' — Force download (Content-Disposition: attachment). Same termination requirement as 'I'.
   *   'S' — Return raw PDF bytes as a string. No headers are sent.
   *   'F' — Write bytes to a file on disk. $filename MUST be an absolute path to a trusted,
   *           application-controlled directory; never pass user-supplied values here.
   *
   * @param string $mode     'I' | 'D' | 'S' | 'F'
   * @param string $filename Filename for 'D' (Content-Disposition) and absolute path for 'F'
   *
   * @return string Raw PDF bytes (always returned regardless of mode)
   *
   * @throws \LogicException          if called before any pages have been added
   * @throws \InvalidArgumentException if an unsupported mode is supplied, or if 'F' mode
   *                                   receives a non-absolute or path-traversal path
   */
  public function output(string $mode = 'I', string $filename = 'document.pdf'): string
  {
    $mode = strtoupper($mode);
    if (!in_array($mode, ['I', 'D', 'S', 'F'], true)) {
      throw new \InvalidArgumentException(sprintf(
        "Tabularium: unsupported output mode '%s'. Valid modes: I, D, S, F.",
        $mode
      ));
    }
    if ($mode === 'F') {
      // Require an absolute path, no traversal sequences, and an existing parent directory.
      // F mode is for trusted internal use only — never pass user-supplied values.
      $realDir = realpath(dirname($filename));
      if (
        !str_starts_with($filename, '/') ||
        str_contains($filename, '..') ||
        $realDir === false
      ) {
        throw new \InvalidArgumentException(
          'Tabularium: F-mode filename must be an absolute path to an existing directory with no traversal sequences.'
        );
      }
    }
    $pdf = $this->buildPdf();
    match ($mode) {
      'I' => $this->sendToBrowser($pdf, $filename, false),
      'D' => $this->sendToBrowser($pdf, $filename, true),
      'F' => (function () use ($filename, $pdf): void {
        if (file_put_contents($filename, $pdf) === false) {
          throw new \RuntimeException("Tabularium: failed to write PDF to '{$filename}'.");
        }
      })(),
      default => null,   // 'S': string return only
    };
    return $pdf;
  }

  // ---------------------------------------------------------------------------
  // Internal: PDF assembly
  // ---------------------------------------------------------------------------

  /**
   * Assemble the complete PDF 1.4 binary from accumulated page streams.
   *
   * Object layout:
   *   1           → Catalog
   *   2           → Pages dictionary
   *   3, 5, 7, …  → Page objects (odd, starting at 3)
   *   4, 6, 8, …  → Content stream objects (even, starting at 4)
   *   3+2N, …     → Font objects (contiguous block after last content object)
   */
  private function buildPdf(): string
  {
    $pageCount = count($this->pageStreams);
    if ($pageCount === 0) {
      throw new \LogicException('Tabularium: output() called with no pages added.');
    }

    $fontObjBase = 3 + 2 * $pageCount;
    $hasInfo     = ($this->metaTitle !== '' || $this->metaAuthor !== '' || $this->metaSubject !== '');

    // Build font object map: alias → [pdfName, objNum]
    $fontObjMap = [];
    foreach ($this->usedFonts as $pdfName => $alias) {
      $fontObjMap[$alias] = [$pdfName, $fontObjBase + count($fontObjMap)];
    }

    // /Info object number is first slot after all font objects
    $infoObjNum = $fontObjBase + count($fontObjMap);

    // Font resource dict string shared by all page /Resources
    $fontResParts = [];
    foreach ($fontObjMap as $alias => [$pdfName, $objNum]) {
      $fontResParts[] = sprintf('/%s %d 0 R', $alias, $objNum);
    }
    $fontRes = implode(' ', $fontResParts);

    // /Kids list
    $pageObjNums = array_map(fn($i) => 3 + 2 * $i, range(0, $pageCount - 1));
    $kidsStr     = implode(' ', array_map(fn($n) => "$n 0 R", $pageObjNums));

    // Collect all PDF objects
    $objects    = [];
    $catalogInfo = $hasInfo ? sprintf(' /Info %d 0 R', $infoObjNum) : '';
    $objects[1] = sprintf("1 0 obj\n<< /Type /Catalog /Pages 2 0 R%s >>\nendobj", $catalogInfo);
    $objects[2] = sprintf(
      "2 0 obj\n<< /Type /Pages /Kids [%s] /Count %d >>\nendobj",
      $kidsStr, $pageCount
    );

    for ($i = 0; $i < $pageCount; $i++) {
      $pageObj    = 3 + 2 * $i;
      $contentObj = 4 + 2 * $i;
      $stream     = rtrim($this->pageStreams[$i]);
      $objects[$pageObj] = sprintf(
        "%d 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << %s >> >> >>\nendobj",
        $pageObj, $this->pageWidth, $this->pageHeight, $contentObj, $fontRes
      );
      $objects[$contentObj] = sprintf(
        "%d 0 obj\n<< /Length %d >>\nstream\n%s\nendstream\nendobj",
        $contentObj, strlen($stream), $stream
      );
    }

    foreach ($fontObjMap as $alias => [$pdfName, $objNum]) {
      $objects[$objNum] = sprintf(
        "%d 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /%s /Encoding /WinAnsiEncoding >>\nendobj",
        $objNum, $pdfName
      );
    }

    // /Info dictionary (optional; only emitted when setMeta() was called)
    if ($hasInfo) {
      $infoParts = ['/Creator (Tabularium/PayCal)'];
      if ($this->metaTitle !== '')   { $infoParts[] = '/Title ('   . $this->escape($this->metaTitle)   . ')'; }
      if ($this->metaAuthor !== '')  { $infoParts[] = '/Author ('  . $this->escape($this->metaAuthor)  . ')'; }
      if ($this->metaSubject !== '') { $infoParts[] = '/Subject (' . $this->escape($this->metaSubject) . ')'; }
      $objects[$infoObjNum] = sprintf(
        "%d 0 obj\n<< %s >>\nendobj",
        $infoObjNum, implode(' ', $infoParts)
      );
    }

    // Assemble body, recording byte offsets for the xref table
    ksort($objects);
    $maxObj  = max(array_keys($objects));
    $body    = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";  // header + binary comment (marks file as binary)
    $offsets = [];

    foreach ($objects as $n => $content) {
      $offsets[$n] = strlen($body);
      $body .= $content . "\n";
    }

    // Cross-reference table
    $xrefOffset = strlen($body);
    $body .= "xref\n";
    $body .= sprintf("0 %d\n", $maxObj + 1);
    $body .= "0000000000 65535 f \n";  // free object 0 (required)
    for ($i = 1; $i <= $maxObj; $i++) {
      $body .= isset($offsets[$i])
        ? sprintf("%010d 00000 n \n", $offsets[$i])
        : "0000000000 65535 f \n";
    }

    $body .= "trailer\n";
    $trailerInfo = $hasInfo ? sprintf(' /Info %d 0 R', $infoObjNum) : '';
    $body .= sprintf("<< /Size %d /Root 1 0 R%s >>\n", $maxObj + 1, $trailerInfo);
    $body .= "startxref\n$xrefOffset\n%%EOF\n";

    return $body;
  }

  /**
   * Send PDF bytes to the browser with RFC-compliant headers.
   *
   * Content-Disposition uses both the legacy ASCII `filename=` parameter (for older UA
   * compatibility) and the RFC 5987 `filename*=UTF-8''...` extended parameter (for
   * correct handling of non-ASCII characters by modern user agents).
   * The ASCII fallback is stripped to 7-bit safe characters; the RFC 5987 value is
   * percent-encoded via rawurlencode().
   *
   * Caller must not write additional response bytes after invoking this method.
   * Typical usage: call output('I'|'D') as the last statement, then exit().
   */
  private function sendToBrowser(string $pdf, string $filename, bool $download): void
  {
    $disposition = $download ? 'attachment' : 'inline';
    // Safe ASCII fallback: strip anything outside 0x20–0x7E (except common chars)
    $asciiName = preg_replace('/[^\x20-\x7E]/', '_', basename($filename)) ?? 'document.pdf';
    // RFC 5987 extended parameter (rawurlencode handles UTF-8 percent-encoding)
    $rfcName = rawurlencode(basename($filename));
    header('Content-Type: application/pdf');
    header(sprintf(
      'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
      $disposition,
      $asciiName,
      $rfcName
    ));
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    echo $pdf;
  }

  // ---------------------------------------------------------------------------
  // Internal: stream helpers
  // ---------------------------------------------------------------------------

  /**
   * Append a PDF operator line to the current page's content stream.
   */
  private function out(string $line): void
  {
    if ($this->pageIndex < 0) {
      throw new \LogicException('Tabularium: call addPage() before any drawing method.');
    }
    $this->pageStreams[$this->pageIndex] .= $line . "\n";
  }

  /**
   * Convert a top-left Y user coordinate to PDF bottom-left Y coordinate.
   */
  private function pdfY(float $y): float
  {
    return $this->pageHeight - $y;
  }

  /**
   * Emit the initial graphics state operators at the start of a new page.
   * Sets line width and default stroke / fill colors so the content stream
   * starts in a known state regardless of what a previous page left behind.
   */
  private function emitPageState(): void
  {
    $this->out(sprintf('%.2f w', $this->lineWidth));
    $this->out(sprintf('%.3f %.3f %.3f RG', $this->drawR, $this->drawG, $this->drawB));
    $this->out(sprintf('%.3f %.3f %.3f rg', $this->textR, $this->textG, $this->textB));
  }

  /**
   * Register a PDF font name and return its resource alias (F1, F2, …).
   * Fonts are registered on first use and reused for the life of the document.
   */
  private function registerFont(string $pdfName): string
  {
    if (!isset($this->usedFonts[$pdfName])) {
      $this->usedFonts[$pdfName] = 'F' . (count($this->usedFonts) + 1);
    }
    return $this->usedFonts[$pdfName];
  }

  /**
   * Sanitize then escape a string for use inside a PDF literal string ( … ).
   * sanitizeText() runs first, stripping control chars and transliterating accents.
   * Then the three PDF stream special characters are escaped: \ ( )
   */
  private function escape(string $s): string
  {
    $s = $this->sanitizeText($s);
    return str_replace(
      ['\\',   '(',   ')'],
      ['\\\\', '\\(', '\\)'],
      $s
    );
  }
}
