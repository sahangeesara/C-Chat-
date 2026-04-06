const { defineConfig } = require('@vue/cli-service')
const webpack = require('webpack')
module.exports = defineConfig({
  transpileDependencies: true,
  
  devServer: {
    host: '127.0.0.1',
    port: 3000,
    allowedHosts: 'all',
    // Keep dev websocket local even if the page is opened via tunnel URLs.
    client: {
      webSocketURL: {
        protocol: 'ws',
        hostname: '127.0.0.1',
        port: 3000,
        pathname: '/ws'
      }
    },
    hot: false,
    liveReload: false,
    proxy: {
      '/api/weather': {
        target: 'https://api.open-meteo.com',
        changeOrigin: true,
        pathRewrite: { '^/api/weather': '/v1/forecast' }
      },
      '/api/location': {
        target: 'https://geocoding-api.open-meteo.com',
        changeOrigin: true,
        pathRewrite: { '^/api/location': '/v1/reverse' }
      }
    }
  },
  configureWebpack: {
    resolve: {
      fallback: {
        "fs": false,
        "http": require.resolve("stream-http"),
        "https": require.resolve("https-browserify"),
        "url": require.resolve("url/"),
        "stream": require.resolve("stream-browserify"),
        "assert": require.resolve("assert/"),
        "crypto": require.resolve("crypto-browserify"),
        "path": require.resolve("path-browserify"),
        "zlib": require.resolve("browserify-zlib"),
        "util": require.resolve("util/"),
        "process": require.resolve("process/browser"),
        "buffer": require.resolve("buffer/")
      }
    },
    plugins: [
      new webpack.ProvidePlugin({
        process: "process/browser",
        Buffer: ["buffer", "Buffer"]
      }),
      new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: JSON.stringify(true),
        __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false)
      })
    ]
  }
})
