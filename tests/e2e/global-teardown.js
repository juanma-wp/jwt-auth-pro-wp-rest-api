/**
 * Global teardown for Playwright tests
 * Runs once after all tests
 */

export default async function globalTeardown() {
  console.log('🧹 Cleaning up test environment...');

  // Add any global cleanup logic here
  // For example:
  // - Stop mock servers
  // - Clean up test databases
  // - Reset external services

  console.log('✓ Test environment cleaned up');
}
