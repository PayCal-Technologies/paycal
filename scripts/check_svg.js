const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const page = await browser.newPage();
  
  try {
    // Navigate to teams page
    await page.goto('https://mac.paycal.local/teams/', { waitUntil: 'networkidle2', timeout: 10000 });
    
    // Get the SVG from the nav
    const svgData = await page.evaluate(() => {
      const svg = document.querySelector('a[href="/teams/"] svg');
      if (svg) {
        return {
          outerHTML: svg.outerHTML,
          pathD: svg.querySelector('path')?.getAttribute('d') || 'NO PATH',
          viewBox: svg.getAttribute('viewBox')
        };
      }
      return { error: 'SVG not found' };
    });
    
    console.log('SVG Data from Browser:');
    console.log(JSON.stringify(svgData, null, 2));
    
    // Check for any console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('BROWSER ERROR:', msg.text());
      }
    });
    
  } catch (error) {
    console.error('Error:', error.message);
  } finally {
    await browser.close();
  }
})();
