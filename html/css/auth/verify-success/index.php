<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
header('Content-type: text/css');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: var(--sans-serif);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 40px;
    max-width: 500px;
    width: 100%;
    text-align: center;
  }
  .icon {
    width: 80px;
    height: 80px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
  }
  .icon svg {
    width: 48px;
    height: 48px;
    stroke: white;
    stroke-width: 3;
    fill: none;
  }
  h1 {
    color: #1f2937;
    font-size: 28px;
    margin-bottom: 16px;
  }
  p {
    color: #6b7280;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 24px;
  }
  .info-box {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 20px;
    margin: 24px 0;
    text-align: left;
  }
  .info-box h3 {
    color: #1f2937;
    font-size: 16px;
    margin-bottom: 12px;
  }
  .info-box ul {
    list-style: none;
    padding: 0;
  }
  .info-box li {
    color: #4b5563;
    font-size: 14px;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
  }
  .info-box li:last-child {
    border-bottom: none;
  }
  .info-box li::before {
    content: '✓';
    color: #10b981;
    font-weight: bold;
    margin-right: 8px;
  }
  .button {
    display: inline-block;
    background: #667eea;
    color: white;
    text-decoration: none;
    padding: 14px 32px;
    border-radius: 8px;
    font-weight: 500;
    transition: background 0.2s;
  }
  .button:hover {
    background: #5568d3;
  }
