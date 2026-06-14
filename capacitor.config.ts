import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.reflect.app',
  appName: 'Reflect App',
  webDir: 'public',
  server: {
    url: 'https://hansei.up.railway.app/'
  }
};

export default config;
