#!/usr/bin/env node

const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const page = await browser.newPage();
  
  // Arrays to store console messages
  const consoleMessages = [];
  
  // Listen to all console events
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    const location = msg.location();
    
    consoleMessages.push({
      type,
      text,
      url: location.url,
      line: location.lineNumber
    });
  });
  
  // Listen to page errors
  page.on('pageerror', error => {
    consoleMessages.push({
      type: 'pageerror',
      text: error.toString(),
      stack: error.stack
    });
  });
  
  // Capture POST requests for debugging
  page.on('request', request => {
    if (request.method() === 'POST') {
      console.log(`📤 POST to: ${request.url()}`);
      const postData = request.postData();
      if (postData) {
        console.log(`   Payload: ${postData.substring(0, 200)}`);
      }
    }
  });
  
  try {
    console.log('🌐 Navigating to login page...');
    await page.goto('http://mac.paycal.local/login/', { 
      waitUntil: 'networkidle2',
      timeout: 30000 
    });
    
    console.log(`Total console messages so far: ${consoleMessages.length}`);
    
    console.log('📝 Filling in login credentials...');
    await page.waitForSelector('#email');
    await page.type('#email', 'cshaiku@gmail.com');
    await page.type('#current-password', 'ma9am1');
    
    console.log('🔑 Submitting login form...');
    
    // Wait for navigation after submit
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 5000 }).catch((e) => {
        console.log('⚠️  No navigation occurred after form submit');
      })
    ]);
    
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Check for login error message
    const signinMessage = await page.$eval('#signin_message', el => el.textContent.trim()).catch(() => '');
    if (signinMessage) {
      console.log(`📋 Login message: "${signinMessage}"`);
    }
    
    // Check if we have a session cookie
    const cookies = await page.cookies();
    const sessionCookie = cookies.find(c => c.name.includes('session') || c.name.includes('PHPSESSID'));
    
    if (sessionCookie) {
      console.log(`✅ Session established (${sessionCookie.name})`);
      console.log(`   Value: ${sessionCookie.value.substring(0, 20)}...`);
    } else {
      console.log('❌ No session cookie - login failed');
    }
    
    // Don't clear console messages - keep them all
    console.log(`📊 Console messages from login: ${consoleMessages.length}`);
    
    // First confirm we can access the main page
    console.log('\n🏠 Navigating to home page to confirm authentication...');
    await page.goto('http://mac.paycal.local/', { 
      waitUntil: 'networkidle2',
      timeout: 30000 
    });
    
    const homeUrl = page.url();
    const homeTitle = await page.title();
    console.log(`📍 Home URL: ${homeUrl}`);
    console.log(`📄 Home Title: ${homeTitle}`);
    
    // Check what scripts are loaded on home page 
    const homeScripts = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('script[src]')).map(s => s.src.split('?')[0].split('/').pop());
    });
    console.log(`📜 Scripts on home: ${homeScripts.join(', ')}`);
    
    if (homeScripts.includes('signin')) {
      console.log('❌ Still on login page - authentication failed');
      process.exit(1);
    }
    
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Now navigate to earnings with the session cookie
    console.log('\n💰 Navigating to /earnings/ page...');
    await page.goto('http://mac.paycal.local/earnings/', { 
      waitUntil: 'networkidle2',
      timeout: 30000 
    });
    
    const earningsUrl = page.url();
    console.log(`📍 Earnings URL: ${earningsUrl}`);
    
    // Check page title
    const title = await page.title();
    console.log(`📄 Page title: ${title}`);
    
    // Check if we have earnings-specific content or got redirected
    const pageContent = await page.content();
    const hasEarningsContent = pageContent.includes('earnings') || pageContent.includes('Earnings');
    console.log(`💼 Has earnings content: ${hasEarningsContent}`);
    
    // Try to see what page we're actually on
    const bodyText = await page.evaluate(() => {
      const h1 = document.querySelector('h1');
      const h2 = document.querySelector('h2');
      return {
        h1: h1 ? h1.textContent : null,
        h2: h2 ? h2.textContent : null,
        url: window.location.href
      };
    });
    console.log(`📰 Page heading: ${bodyText.h1 || bodyText.h2 || 'none'}`);
    console.log(`🌐 Window location: ${bodyText.url}`);
    
    console.log('⏳ Waiting for page to fully load and execute...');
    await new Promise(resolve => setTimeout(resolve, 5000)); // Give JS more time to execute
    
    // Check what scripts are loaded
    const scripts = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('script[src]')).map(s => s.src);
    });
    console.log(`📜 Scripts loaded: ${scripts.length}`);
    scripts.forEach(s => console.log(`   - ${s}`));
    
    console.log(`📊 Total console messages captured: ${consoleMessages.length}`);
    
    console.log('\n═══════════════════════════════════════════════════════');
    console.log('📋 CAPTURED CONSOLE MESSAGES FROM /earnings:');
    console.log('═══════════════════════════════════════════════════════\n');
    
    // Filter and display messages
    const errors = consoleMessages.filter(m => m.type === 'error' || m.type === 'pageerror');
    const warnings = consoleMessages.filter(m => m.type === 'warning');
    const logs = consoleMessages.filter(m => m.type === 'log' || m.type === 'info');
    
    if (errors.length > 0) {
      console.log(`\n🔴 ERRORS (${errors.length}):\n`);
      errors.forEach((msg, i) => {
        console.log(`[${i + 1}] ${msg.url ? msg.url.split('?')[0] : ''}:${msg.line || '?'}`);
        console.log(`    ${msg.text}`);
        if (msg.stack) console.log(`    Stack: ${msg.stack.substring(0, 200)}...`);
        console.log('');
      });
    }
    
    if (warnings.length > 0) {
      console.log(`\n⚠️  WARNINGS (${warnings.length}):\n`);
      warnings.forEach((msg, i) => {
        console.log(`[${i + 1}] ${msg.url ? msg.url.split('?')[0] : ''}:${msg.line || '?'}`);
        console.log(`    ${msg.text}`);
        console.log('');
      });
    }
    
    if (logs.length > 0) {
      console.log(`\n📝 LOGS (showing first 20):\n`);
      logs.slice(0, 20).forEach((msg, i) => {
        console.log(`[${i + 1}] ${msg.url ? msg.url.split('?')[0] : ''}:${msg.line || '?'}`);
        console.log(`    ${msg.text}`);
        console.log('');
      });
      if (logs.length > 20) {
        console.log(`... and ${logs.length - 20} more log messages`);
      }
    }
    
    console.log('\n═══════════════════════════════════════════════════════');
    console.log(`Total messages captured: ${consoleMessages.length}`);
    console.log('═══════════════════════════════════════════════════════\n');
    
  } catch (error) {
    console.error('❌ Error during automation:', error);
  } finally {
    await browser.close();
  }
})();
