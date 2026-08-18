import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PAYCAL_SMOKE_BASE_URL || 'https://mac.paycal.app';
const workers = Number.parseInt(process.env.PAYCAL_SMOKE_WORKERS || '', 10);

export default defineConfig({
  testDir: './tests/smoke-ui',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: Number.isFinite(workers) && workers > 0 ? workers : undefined,
  reporter: process.env.CI ? [['github'], ['list']] : [['list']],
  timeout: 45000,
  expect: {
    timeout: 10000,
  },
  use: {
    baseURL,
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
