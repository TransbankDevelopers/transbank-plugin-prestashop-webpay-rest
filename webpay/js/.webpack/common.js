const path = require("path");

// replace admin029fgzh0pk6zpmqstkc with your PS admin folder name
const psRootDir = path.resolve(
  process.env.PWD,
  "../../../admin029fgzh0pk6zpmqstkc/themes/new-theme/"
);
const psJsDir = path.resolve(psRootDir, "js");

module.exports = {
  entry: {
    transactions: ["../js/transactions"],
  },
  output: {
    path: path.resolve(__dirname, "../js"),
    filename: "[name].bundle.js",
    publicPath: "public",
  },
  resolve: {
    extensions: [".js", ".ts"],
    alias: {
      "@PSJs": psJsDir,
      "@app": psJsDir + "/app",
      "@components": psJsDir + "/components",
    },
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        include: path.resolve(__dirname, "js"),
        loader: "esbuild-loader",
        options: {
          loader: "jsx",
          target: "es2015",
        },
      },
      {
        test: /\.ts?$/,
        loader: "ts-loader",
        options: {
          onlyCompileBundledFiles: true,
        },
        exclude: /node_modules/,
      },
    ],
  },
  plugins: [],
};
