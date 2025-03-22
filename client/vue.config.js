const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,

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
      new (require("webpack")).ProvidePlugin({
        process: "process/browser",
        Buffer: ["buffer", "Buffer"]
      })
    ]
  }
})
