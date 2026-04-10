import { PDFDocument, StandardFonts, rgb } from '/js/third-party/pdf-lib/index.js';
import { resolveUserLocale } from '/js/earnings/locale.js';

const PAGE_WIDTH = 595.28;
const PAGE_HEIGHT = 841.89;

const MARGIN_LEFT = 50;
const MARGIN_TOP = 50;
const TABLE_HEADER_GAP = 24;
const TABLE_ROW_HEIGHT = 20;
const FOOTER_LINE_Y = 74;
const FOOTER_CREATED_TEXT_Y = 56;
const FOOTER_REFERENCE_TEXT_Y = 42;
const SUMMARY_LINE_GAP = 15;
const SUMMARY_TOP_GAP = 16;
const SUMMARY_MIN_BOTTOM_Y = FOOTER_LINE_Y + 18;
const TABLE_SECTION_TOP_GAP = 16;
const TABLE_SECTION_RULE_GAP = TABLE_ROW_HEIGHT;
const MONTH_SECTION_TOP_GAP = 16;
const MONTH_SECTION_RULE_GAP = TABLE_ROW_HEIGHT;
const MONTH_SECTION_AFTER_LABEL_GAP = 16;
const MONTH_SECTION_AFTER_BLOCK_GAP = 12;
const CONTENT_MIN_Y = FOOTER_LINE_Y + 20;
const TABLE_COLUMNS = [
  { key: 'site_name', label: 'Site', width: 160, align: 'left' },
  { key: 'regular', label: 'Regular', width: 72, align: 'right' },
  { key: 'overtime', label: 'Overtime', width: 72, align: 'right' },
  { key: 'gross', label: 'Gross', width: 84, align: 'right' },
  { key: 'net', label: 'Net', width: 84, align: 'right' },
  { key: 'employment_insurance', label: 'EI', width: 60, align: 'right' },
  { key: 'canada_pension_plan', label: 'CPP', width: 60, align: 'right' },
  { key: 'old_age_security', label: 'OAS', width: 60, align: 'right' },
];
const DAILY_COLUMN_DEFS = [
  { key: 'date', label: 'Date', align: 'left' },
  { key: 'site_name', label: 'Site', align: 'left' },
  { key: 'regular', label: 'Regular', align: 'right' },
  { key: 'overtime', label: 'Overtime', align: 'right' },
  { key: 'travel', label: 'Travel', align: 'right' },
  { key: 'loa', label: 'LOA', align: 'right' },
  { key: 'gross', label: 'Gross', align: 'right' },
  { key: 'net', label: 'Net', align: 'right' },
];

const TABLE_FONT_SIZE_DEFAULT = 10;
const TABLE_FONT_SIZE_DAILY = 8;
const TABLE_ROW_HEIGHT_DAILY = 16;
const TABLE_HEADER_GAP_DAILY = 20;

const USER_LOCALE = resolveUserLocale();

function getPageSizeForScope(scope) {
  // Yearly and daily reports use landscape for readability with expanded columns.
  return (scope === 'daily' || scope === 'yearly') ? [PAGE_HEIGHT, PAGE_WIDTH] : [PAGE_WIDTH, PAGE_HEIGHT];
}

function getTableFontSize(scope) {
  return scope === 'daily' ? TABLE_FONT_SIZE_DAILY : TABLE_FONT_SIZE_DEFAULT;
}

function getTableRowHeight(scope) {
  return scope === 'daily' ? TABLE_ROW_HEIGHT_DAILY : TABLE_ROW_HEIGHT;
}

function getTableHeaderGap(scope) {
  return scope === 'daily' ? TABLE_HEADER_GAP_DAILY : TABLE_HEADER_GAP;
}

function getContentWidthForPage(pageWidth) {
  return pageWidth - (MARGIN_LEFT * 2);
}

function buildDailyColumns(pageWidth) {
  const contentWidth = getContentWidthForPage(pageWidth);
  const siteWidth = contentWidth * 0.20;
  const nonSiteColumnCount = DAILY_COLUMN_DEFS.length - 1;
  const nonSiteWidth = (contentWidth - siteWidth) / nonSiteColumnCount;

  return DAILY_COLUMN_DEFS.map((col) => ({
    ...col,
    width: col.key === 'site_name' ? siteWidth : nonSiteWidth,
  }));
}

export async function renderPayCalPdf(report) {
  const pdfDoc = await PDFDocument.create();

  const font = await pdfDoc.embedFont(StandardFonts.Helvetica);
  const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

  const pageContext = createReportPage(pdfDoc, {
    fontBold,
    font,
    meta: report?.meta || {},
  });

  const rows = Array.isArray(report?.rows) ? report.rows : [];
  const scope = String(report?.meta?.scope || 'yearly').toLowerCase();
  const tableLayout = scope === 'monthly'
    ? drawMonthlyTable(pdfDoc, pageContext, font, fontBold, rows, report?.meta || {})
    : drawTable(pdfDoc, pageContext, font, fontBold, rows, report?.meta || {});

  drawSummary(pdfDoc, tableLayout, fontBold, font, report?.summary || {}, report?.meta || {});

  return await pdfDoc.save();
}

function createReportPage(pdfDoc, { fontBold, font, meta }) {
  const scope = String(meta?.scope || 'yearly').toLowerCase();
  const page = pdfDoc.addPage(getPageSizeForScope(scope));
  let cursorY = page.getHeight() - MARGIN_TOP;

  cursorY = drawTitle(
    page,
    fontBold,
    buildTitleLine(meta || {}),
    cursorY,
  );

  cursorY = drawMeta(page, font, meta || {}, cursorY);
  drawFooter(page, font, meta || {});

  return {
    page,
    cursorY,
    pdfDoc,
    font,
    fontBold,
    meta: meta || {},
  };
}

function addContinuationPage(layout) {
  return createReportPage(layout.pdfDoc, {
    fontBold: layout.fontBold,
    font: layout.font,
    meta: layout.meta,
  });
}

function buildTitleLine(meta = {}) {
  const title = String(meta.title || 'PayCal Report').trim();
  const asAt = String(meta.as_at || meta.subtitle || '').trim();

  if (asAt === '' || title.toLowerCase().includes(' as at ')) {
    return title;
  }

  return `${title} ${asAt}`.trim();
}

function normalizeIdentity(meta = {}) {
  const fullName = String(meta.full_name || meta.employee || '').trim();
  const email = String(meta.email || '').trim();
  const address = String(meta.address || meta.address_line || '').trim();
  const phone = String(meta.phone || '').trim();
  const city = String(meta.city || '').trim();
  const province = String(meta.province || '').trim();
  const postal = String(meta.postal || '').trim();
  const location = [city, province, postal].filter((part) => part !== '').join(', ');

  return {
    fullName,
    email,
    address,
    phone,
    location,
  };
}

function drawTitle(page, titleFont, text, y) {
  drawCenteredText(page, titleFont, String(text || ''), y, 15, { color: rgb(0, 0, 0) });
  return y - 26;
}

function drawMeta(page, font, meta, y) {
  const identity = normalizeIdentity(meta);
  const tableWidth = page.getWidth() - (MARGIN_LEFT * 2);
  const leftWidth = tableWidth * 0.5;
  const lineHeight = 14;

  // Gap between title and identity block
  let rowY = y - 12;

  const rows = [
    [identity.fullName, identity.email],
    [identity.address, identity.phone],
    [identity.location, ''],
  ];

  for (const [leftCell, rightCell] of rows) {
    if (leftCell !== '') {
      page.drawText(String(leftCell), {
        x: MARGIN_LEFT,
        y: rowY,
        size: 9,
        font,
      });
    }

    if (rightCell !== '') {
      const rightText = String(rightCell);
      const textWidth = font.widthOfTextAtSize(rightText, 9);
      page.drawText(rightText, {
        x: MARGIN_LEFT + leftWidth + leftWidth - textWidth,
        y: rowY,
        size: 9,
        font,
      });
    }

    rowY -= lineHeight;
  }

  return rowY - 8;
}

function getTableColumns(scope, pageWidth = PAGE_WIDTH) {
  return scope === 'daily' ? buildDailyColumns(pageWidth) : TABLE_COLUMNS;
}

function drawTable(pdfDoc, layout, font, fontBold, rows, meta) {
  const scope = String(meta?.scope || 'yearly').toLowerCase();
  const columns = getTableColumns(scope, layout.page.getWidth());
  const rowHeight = getTableRowHeight(scope);
  const headerGap = getTableHeaderGap(scope);
  const fontSize = getTableFontSize(scope);
  let currentLayout = layout;
  let y = startStandardTableSection(currentLayout.page, fontBold, currentLayout.cursorY, columns, {
    rowHeight,
    headerGap,
    fontSize,
  });

  for (const row of rows) {
    if ((y - rowHeight) < CONTENT_MIN_Y) {
      currentLayout = addContinuationPage(currentLayout);
      y = startStandardTableSection(currentLayout.page, fontBold, currentLayout.cursorY, columns, {
        rowHeight,
        headerGap,
        fontSize,
      });
    }

    drawDataRow(currentLayout.page, font, columns, row, y, fontSize);
    y -= rowHeight;
  }

  return {
    ...currentLayout,
    cursorY: y,
  };
}

function startStandardTableSection(page, fontBold, startY, columns, options = {}) {
  const rowHeight = Number(options.rowHeight || TABLE_ROW_HEIGHT);
  const headerGap = Number(options.headerGap || TABLE_HEADER_GAP);
  const fontSize = Number(options.fontSize || TABLE_FONT_SIZE_DEFAULT);
  let y = startY - TABLE_SECTION_TOP_GAP;

  page.drawLine({
    start: { x: MARGIN_LEFT, y },
    end: { x: page.getWidth() - MARGIN_LEFT, y },
    thickness: 0.5,
    color: rgb(0, 0, 0),
  });
  y -= rowHeight;

  drawHeaderRow(page, fontBold, columns, y, fontSize);
  y -= headerGap;

  return y;
}

function drawMonthlyTable(pdfDoc, layout, font, fontBold, rows, meta) {
  const columns = TABLE_COLUMNS;

  const sortedRows = [...rows].sort((a, b) => {
    const monthCompare = String(a?.month || '').localeCompare(String(b?.month || ''));
    if (monthCompare !== 0) {
      return monthCompare;
    }

    return String(a?.site_name || '').localeCompare(String(b?.site_name || ''));
  });

  const grouped = new Map();
  for (const row of sortedRows) {
    const month = String(row.month || 'Unknown');
    if (!grouped.has(month)) {
      grouped.set(month, []);
    }
    grouped.get(month).push(row);
  }

  let currentLayout = layout;
  let y = currentLayout.cursorY;
  const monthLabelOptions = { month: 'long' };

  for (const [month, monthRows] of grouped.entries()) {
    let sectionStarted = false;

    for (const row of monthRows) {
      if (!sectionStarted) {
        const sectionHeight = MONTH_SECTION_TOP_GAP + MONTH_SECTION_RULE_GAP + MONTH_SECTION_AFTER_LABEL_GAP + TABLE_HEADER_GAP + TABLE_ROW_HEIGHT;
        if ((y - sectionHeight) < CONTENT_MIN_Y) {
          currentLayout = addContinuationPage(currentLayout);
          y = currentLayout.cursorY;
        }

        y = startMonthlySection(currentLayout.page, fontBold, y, columns, month, monthLabelOptions);
        sectionStarted = true;
      }

      if ((y - TABLE_ROW_HEIGHT) < CONTENT_MIN_Y) {
        currentLayout = addContinuationPage(currentLayout);
        y = startMonthlySection(currentLayout.page, fontBold, currentLayout.cursorY, columns, month, monthLabelOptions);
        sectionStarted = true;
      }

      drawDataRow(currentLayout.page, font, columns, row, y);
      y -= TABLE_ROW_HEIGHT;
    }

    y -= MONTH_SECTION_AFTER_BLOCK_GAP;
  }

  return {
    ...currentLayout,
    cursorY: y,
  };
}

function startMonthlySection(page, fontBold, startY, columns, month, monthLabelOptions) {
  let y = startY - MONTH_SECTION_TOP_GAP;

  page.drawLine({
    start: { x: MARGIN_LEFT, y },
    end: { x: page.getWidth() - MARGIN_LEFT, y },
    thickness: 0.5,
    color: rgb(0, 0, 0),
  });
  y -= MONTH_SECTION_RULE_GAP;

  const monthText = formatMonthLabel(month, monthLabelOptions);
  page.drawText(monthText, {
    x: MARGIN_LEFT,
    y,
    size: 12,
    font: fontBold,
  });
  y -= MONTH_SECTION_AFTER_LABEL_GAP;

  drawHeaderRow(page, fontBold, columns, y);
  y -= TABLE_HEADER_GAP;

  return y;
}

function formatMonthLabel(monthKey, options) {
  const [year, month] = String(monthKey).split('-');
  const parsedYear = Number(year);
  const parsedMonth = Number(month);

  if (Number.isFinite(parsedYear) && Number.isFinite(parsedMonth) && parsedMonth >= 1 && parsedMonth <= 12) {
    const date = new Date(parsedYear, parsedMonth - 1, 1);
    return date.toLocaleString(USER_LOCALE, options);
  }

  return String(monthKey);
}

function drawHeaderRow(page, font, columns, y, fontSize = TABLE_FONT_SIZE_DEFAULT) {
  let x = MARGIN_LEFT;

  for (const col of columns) {
    const textWidth = font.widthOfTextAtSize(col.label, fontSize);
    const drawX = col.align === 'right' ? (x + col.width - textWidth) : x;

    page.drawText(col.label, {
      x: drawX,
      y,
      size: fontSize,
      font,
    });

    x += col.width;
  }
}

function drawDataRow(page, font, columns, row, y, fontSize = TABLE_FONT_SIZE_DEFAULT) {
  let x = MARGIN_LEFT;

  for (const col of columns) {
    const raw = row[col.key];
    const value = formatCell(col.key, raw);

    const textWidth = font.widthOfTextAtSize(value, fontSize);

    let drawX = x;

    if (col.align === 'right') {
      drawX = x + col.width - textWidth;
    }

    page.drawText(value, {
      x: drawX,
      y,
      size: fontSize,
      font,
    });

    x += col.width;
  }
}

function drawSummary(pdfDoc, layout, fontBold, font, summary, meta) {
  const scope = String(meta?.scope || 'yearly').toLowerCase();
  const totalsLabel = scope === 'monthly' ? 'Monthly' : (scope === 'daily' ? 'Daily' : 'YTD');
  const lines = [
    { label: `Gross ${totalsLabel}`, value: money(summary.gross), size: 11 },
    { label: 'Federal Tax', value: money(summary.federal_tax), size: 10 },
    { label: 'Provincial Tax', value: money(summary.provincial_tax), size: 10 },
    { label: 'EI', value: money(summary.employment_insurance), size: 10 },
    { label: 'CPP', value: money(summary.canada_pension_plan), size: 10 },
    { label: 'OAS', value: money(summary.old_age_security), size: 10 },
    { label: `Taxes ${totalsLabel}`, value: money(summary.taxes), size: 11 },
    { label: `Net ${totalsLabel}`, value: money(summary.net), size: 12 },
  ];

  let summaryLayout = layout;
  let topY = layout.cursorY - SUMMARY_TOP_GAP;
  const bottomY = topY - ((lines.length - 1) * SUMMARY_LINE_GAP);

  if (bottomY < SUMMARY_MIN_BOTTOM_Y) {
    summaryLayout = addContinuationPage(layout);
    topY = summaryLayout.cursorY - SUMMARY_TOP_GAP;
  }

  const rightX = summaryLayout.page.getWidth() - MARGIN_LEFT;

  lines.forEach((line, index) => {
    const text = `${line.label}: ${line.value}`;
    const textWidth = fontBold.widthOfTextAtSize(text, line.size);

    summaryLayout.page.drawText(text, {
      x: rightX - textWidth,
      y: topY - (index * SUMMARY_LINE_GAP),
      size: line.size,
      font: fontBold,
    });
  });
}

function drawFooter(page, font, meta) {
  page.drawLine({
    start: { x: MARGIN_LEFT, y: FOOTER_LINE_Y },
    end: { x: page.getWidth() - MARGIN_LEFT, y: FOOTER_LINE_Y },
    thickness: 1,
    color: rgb(0, 0, 0),
  });

  const createdText = `Created: ${String(meta.created_at || '')} from IP Address ${String(meta.ip_address || 'unknown')}`;
  drawCenteredText(page, font, createdText, FOOTER_CREATED_TEXT_Y, 8);

  const referenceText = `REF: ${String(meta.reference_code || '')}`;
  drawCenteredText(page, font, referenceText, FOOTER_REFERENCE_TEXT_Y, 9);
}

function drawCenteredText(page, font, text, y, size, options = {}) {
  const value = String(text || '');
  const textWidth = font.widthOfTextAtSize(value, size);
  const contentWidth = page.getWidth() - (MARGIN_LEFT * 2);

  page.drawText(value, {
    x: MARGIN_LEFT + ((contentWidth - textWidth) / 2),
    y,
    size,
    font,
    ...options,
  });
}

function formatCell(key, value) {
  if (key === 'gross' || key === 'tax' || key === 'employment_insurance' || key === 'canada_pension_plan' || key === 'old_age_security' || key === 'loa' || key === 'net') {
    return money(value);
  }

  if (key === 'regular' || key === 'overtime' || key === 'travel') {
    return Number(value || 0).toFixed(2);
  }

  return String(value || '');
}

function money(v) {
  const amount = Number(v || 0);

  return `$${amount.toLocaleString(USER_LOCALE, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}
